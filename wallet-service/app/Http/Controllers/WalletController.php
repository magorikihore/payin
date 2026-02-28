<?php

namespace App\Http\Controllers;

use App\Models\InternalTransfer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    private array $operators = ['M-Pesa', 'Tigo Pesa', 'Airtel Money', 'Halopesa'];
    private array $walletTypes = ['collection', 'disbursement'];

    /**
     * Get all wallets (collection + disbursement per operator) + overall balances.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;
        $currency = $user->account['currency'] ?? 'TZS';

        // Ensure wallets exist for all operators and both types
        foreach ($this->operators as $operator) {
            foreach ($this->walletTypes as $type) {
                Wallet::firstOrCreate(
                    ['account_id' => $accountId, 'operator' => $operator, 'wallet_type' => $type],
                    ['user_id' => $user->id, 'balance' => 0, 'currency' => $currency, 'status' => 'active']
                );
            }
        }

        $wallets = Wallet::where('account_id', $accountId)->get();

        $collectionWallets = $wallets->where('wallet_type', 'collection')->values();
        $disbursementWallets = $wallets->where('wallet_type', 'disbursement')->values();

        $collectionTotal = $collectionWallets->sum('balance');
        $disbursementTotal = $disbursementWallets->sum('balance');
        $overallBalance = $collectionTotal + $disbursementTotal;

        // Recent transactions across all wallets
        $walletIds = $wallets->pluck('id');
        $recentTransactions = WalletTransaction::whereIn('wallet_id', $walletIds)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($txn) {
                $wallet = $txn->wallet;
                $txn->operator = $wallet->operator ?? null;
                $txn->wallet_type = $wallet->wallet_type ?? null;
                return $txn;
            });

        return response()->json([
            'collection_wallets' => $collectionWallets,
            'disbursement_wallets' => $disbursementWallets,
            'collection_total' => number_format($collectionTotal, 2, '.', ''),
            'disbursement_total' => number_format($disbursementTotal, 2, '.', ''),
            'overall_balance' => number_format($overallBalance, 2, '.', ''),
            'currency' => $currency,
            'operators' => $this->operators,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Credit a specific operator's collection wallet (payin - money coming in).
     * Charges are calculated and deducted automatically.
     */
    public function credit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $operator = $request->operator;
        $accountId = $user->account_id;
        $grossAmount = (float) $request->amount;
        $token = $request->bearerToken();
        $currency = $user->account['currency'] ?? 'TZS';

        // Calculate charges from transaction-service
        $platformCharge = 0;
        $operatorCharge = 0;
        $totalCharge = 0;

        try {
            $chargeRes = Http::withHeaders([
                'X-Service-Key' => config('services.internal_service_key'),
                'Accept' => 'application/json',
            ])->post(config('services.transaction_service.url') . '/api/internal/charges/calculate', [
                'amount' => $grossAmount,
                'operator' => $operator,
                'transaction_type' => 'collection',
                'account_id' => $accountId,
            ]);

            if ($chargeRes->ok()) {
                $chargeData = $chargeRes->json();
                $platformCharge = (float) ($chargeData['platform_charge'] ?? 0);
                $operatorCharge = (float) ($chargeData['operator_charge'] ?? 0);
                $totalCharge = (float) ($chargeData['total_charge'] ?? 0);
            }
        } catch (\Exception $e) {
            // If charge service unavailable, proceed without charges
        }

        // User pays only platform charge; operator charge is our cost deducted from platform profit
        $netAmount = $grossAmount - $platformCharge;

        if ($netAmount <= 0) {
            return response()->json(['message' => 'Amount too small to cover charges. Charges: ' . number_format($platformCharge, 2) . ' ' . $currency], 422);
        }

        $txnRef = 'TXN-' . strtoupper(Str::random(12));

        $result = DB::transaction(function () use ($user, $request, $operator, $accountId, $netAmount, $grossAmount, $platformCharge, $operatorCharge, $txnRef, $currency) {
            $wallet = Wallet::lockForUpdate()->firstOrCreate(
                ['account_id' => $accountId, 'operator' => $operator, 'wallet_type' => 'collection'],
                ['user_id' => $user->id, 'balance' => 0, 'currency' => $currency, 'status' => 'active']
            );

            if ($wallet->status !== 'active') {
                return response()->json(['message' => 'Wallet is not active.'], 403);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $netAmount;

            $wallet->update(['balance' => $balanceAfter]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $netAmount,
                'reference' => $txnRef,
                'description' => ($request->description ?? "Payin via {$operator}") . ($platformCharge > 0 ? " (Service Fee: " . number_format($platformCharge, 2) . " {$currency})" : ''),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'metadata' => json_encode([
                    'gross_amount' => $grossAmount,
                    'net_amount' => $netAmount,
                    'platform_charge' => $platformCharge,
                    'operator_charge' => $operatorCharge,
                    'operator' => $operator,
                ]),
            ]);

            return null; // success
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        // Record transaction in transaction-service (fire-and-forget)
        try {
            $serviceKey = config('services.internal_service_key');
            Http::withHeaders([
                'X-Service-Key' => $serviceKey,
                'Accept' => 'application/json',
            ])->post(config('services.transaction_service.url') . '/api/internal/transactions', [
                'account_id' => $accountId,
                'user_id' => $user->id,
                'transaction_ref' => $txnRef,
                'amount' => $grossAmount,
                'type' => 'collection',
                'operator' => $operator,
                'status' => 'completed',
                'platform_charge' => $platformCharge,
                'operator_charge' => $operatorCharge,
                'currency' => $currency,
                'description' => $request->description ?? "Payin via {$operator}",
                'payment_method' => 'mobile_money',
            ]);
        } catch (\Exception $e) {
            // Log silently - wallet was already credited
        }

        // Send webhook callback to business
        $this->sendWebhook($token, $accountId, [
            'event' => 'payin.completed',
            'transaction_ref' => $txnRef,
            'type' => 'collection',
            'operator' => $operator,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'platform_charge' => $platformCharge,
            'operator_charge' => $operatorCharge,
            'currency' => $currency,
            'status' => 'completed',
            'description' => $request->description ?? "Payin via {$operator}",
            'timestamp' => now()->toIso8601String(),
        ]);

        $walletSummary = $this->walletSummary($user, "Collection wallet credited via {$operator}. Gross: " . number_format($grossAmount, 2) . " {$currency}, Service Fee: " . number_format($platformCharge, 2) . " {$currency}, Net: " . number_format($netAmount, 2) . " {$currency}");

        $data = $walletSummary->getData(true);
        $data['charges'] = [
            'gross_amount' => number_format($grossAmount, 2, '.', ''),
            'platform_charge' => number_format($platformCharge, 2, '.', ''),
            'operator_charge' => number_format($operatorCharge, 2, '.', ''),
            'total_charge' => number_format($platformCharge + $operatorCharge, 2, '.', ''),
            'net_amount' => number_format($netAmount, 2, '.', ''),
        ];

        return response()->json($data);
    }

    /**
     * Request transfer from collection to disbursement (requires admin approval).
     */
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $operator = $request->operator;
        $amount = $request->amount;
        $accountId = $user->account_id;

        // Check collection wallet has sufficient balance
        $sourceWallet = Wallet::where('account_id', $accountId)
            ->where('operator', $operator)
            ->where('wallet_type', 'collection')
            ->first();

        if (!$sourceWallet) {
            return response()->json(['message' => 'Collection wallet not found.'], 404);
        }

        if ($sourceWallet->status !== 'active') {
            return response()->json(['message' => 'Collection wallet is not active.'], 403);
        }

        // Include pending transfers when checking balance
        $pendingAmount = InternalTransfer::where('account_id', $accountId)
            ->where('operator', $operator)
            ->where('status', 'pending')
            ->sum('amount');

        if ($sourceWallet->balance < ($amount + $pendingAmount)) {
            return response()->json(['message' => 'Insufficient balance (including pending transfers) in collection wallet.'], 422);
        }

        $transferRef = 'TRF-' . strtoupper(Str::random(12));
        $desc = $request->description ?? "Transfer {$operator}: Collection → Disbursement";

        $transfer = InternalTransfer::create([
            'account_id' => $accountId,
            'operator' => $operator,
            'amount' => $amount,
            'reference' => $transferRef,
            'description' => $desc,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        // Notify admin users about new transfer request
        try {
            Http::post(config('services.auth_service.url') . '/api/internal/send-notification', [
                'account_id' => $accountId,
                'type' => 'transfer_requested',
                'data' => [
                    'reference' => $transferRef,
                    'operator' => $operator,
                    'amount' => $amount,
                    'currency' => $user->account['currency'] ?? 'TZS',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::warning('Admin transfer notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Transfer request submitted. Pending admin approval.',
            'transfer' => $transfer,
        ]);
    }

    /**
     * Get user's internal transfer requests.
     */
    public function myTransfers(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = InternalTransfer::where('account_id', $user->account_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->get();

        return response()->json(['transfers' => $transfers]);
    }

    /**
     * Admin: list all internal transfer requests.
     */
    public function adminTransfers(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = InternalTransfer::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $transfers = $query->get();
        $pendingCount = InternalTransfer::where('status', 'pending')->count();

        return response()->json([
            'transfers' => $transfers,
            'pending_count' => $pendingCount,
        ]);
    }

    /**
     * Admin: approve an internal transfer.
     */
    public function approveTransfer(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $transfer = InternalTransfer::find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found.'], 404);
        }
        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer is not pending.'], 422);
        }

        return DB::transaction(function () use ($transfer, $request) {
            $accountId = $transfer->account_id;
            $operator = $transfer->operator;
            $amount = $transfer->amount;

            // Lock source (collection) wallet
            $sourceWallet = Wallet::lockForUpdate()
                ->where('account_id', $accountId)
                ->where('operator', $operator)
                ->where('wallet_type', 'collection')
                ->first();

            if (!$sourceWallet || $sourceWallet->balance < $amount) {
                return response()->json(['message' => 'Insufficient balance in collection wallet.'], 422);
            }

            // Lock destination (disbursement) wallet
            $destWallet = Wallet::lockForUpdate()->firstOrCreate(
                ['account_id' => $accountId, 'operator' => $operator, 'wallet_type' => 'disbursement'],
                ['user_id' => $transfer->requested_by, 'balance' => 0, 'currency' => $sourceWallet->currency, 'status' => 'active']
            );

            $desc = $transfer->description ?? "Transfer {$operator}: Collection → Disbursement";

            // Debit collection
            $srcBefore = $sourceWallet->balance;
            $srcAfter = $srcBefore - $amount;
            $sourceWallet->update(['balance' => $srcAfter]);

            WalletTransaction::create([
                'wallet_id' => $sourceWallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $transfer->reference . '-OUT',
                'description' => $desc,
                'balance_before' => $srcBefore,
                'balance_after' => $srcAfter,
                'status' => 'completed',
                'metadata' => json_encode(['transfer_to' => 'disbursement', 'operator' => $operator]),
            ]);

            // Credit disbursement
            $destBefore = $destWallet->balance;
            $destAfter = $destBefore + $amount;
            $destWallet->update(['balance' => $destAfter]);

            WalletTransaction::create([
                'wallet_id' => $destWallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'reference' => $transfer->reference . '-IN',
                'description' => $desc,
                'balance_before' => $destBefore,
                'balance_after' => $destAfter,
                'status' => 'completed',
                'metadata' => json_encode(['transfer_from' => 'collection', 'operator' => $operator]),
            ]);

            // Update transfer status
            $transfer->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            // Notify account owner via auth-service
            try {
                Http::post(config('services.auth_service.url') . '/api/internal/send-notification', [
                    'account_id' => $accountId,
                    'type' => 'transfer_approved',
                    'data' => [
                        'reference' => $transfer->reference,
                        'operator' => $transfer->operator,
                        'amount' => $transfer->amount,
                        'currency' => $sourceWallet->currency ?? 'TZS',
                    ],
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Transfer approval notification failed: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Transfer approved and executed.',
                'transfer' => $transfer->fresh(),
            ]);
        });
    }

    /**
     * Admin: reject an internal transfer.
     */
    public function rejectTransfer(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $transfer = InternalTransfer::find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found.'], 404);
        }
        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer is not pending.'], 422);
        }

        $transfer->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes ?? null,
        ]);

        return response()->json([
            'message' => 'Transfer rejected.',
            'transfer' => $transfer->fresh(),
        ]);
    }

    /**
     * Debit collection wallet for settlement (called by settlement-service).
     */
    public function debitSettlement(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'settlement_ref' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $operator = $request->operator;
        $amount = $request->amount;

        $accountId = $user->account_id;

        return DB::transaction(function () use ($user, $operator, $amount, $request, $accountId) {
            $wallet = Wallet::lockForUpdate()
                ->where('account_id', $accountId)
                ->where('operator', $operator)
                ->where('wallet_type', 'collection')
                ->first();

            if (!$wallet) {
                return response()->json(['message' => 'Collection wallet not found for ' . $operator . '.'], 404);
            }

            if ($wallet->status !== 'active') {
                return response()->json(['message' => 'Collection wallet is not active.'], 403);
            }

            if ($wallet->balance < $amount) {
                return response()->json(['message' => 'Insufficient collection balance for ' . $operator . '. Available: ' . number_format($wallet->balance, 2) . ' ' . $wallet->currency], 422);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $wallet->update(['balance' => $balanceAfter]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => 'STL-' . strtoupper(Str::random(12)),
                'description' => $request->description ?? 'Settlement debit via ' . $operator,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'metadata' => json_encode(['settlement_ref' => $request->settlement_ref, 'operator' => $operator]),
            ]);

            return response()->json([
                'message' => 'Collection wallet debited for settlement.',
                'balance_after' => number_format($balanceAfter, 2, '.', ''),
            ]);
        });
    }

    /**
     * List wallet transactions across all operators and wallet types.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;
        $wallets = Wallet::where('account_id', $accountId)->get();

        if ($wallets->isEmpty()) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $walletIds = $wallets->pluck('id');
        $query = WalletTransaction::whereIn('wallet_id', $walletIds)
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('operator')) {
            $opWalletIds = $wallets->where('operator', $request->operator)->pluck('id');
            $query->whereIn('wallet_id', $opWalletIds);
        }

        if ($request->filled('wallet_type')) {
            $typeWalletIds = $wallets->where('wallet_type', $request->wallet_type)->pluck('id');
            $query->whereIn('wallet_id', $typeWalletIds);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        $results = $query->paginate(15);

        // Append operator and wallet_type to each transaction
        $walletInfo = $wallets->mapWithKeys(function ($w) {
            return [$w->id => ['operator' => $w->operator, 'wallet_type' => $w->wallet_type]];
        });

        $results->getCollection()->transform(function ($txn) use ($walletInfo) {
            $info = $walletInfo[$txn->wallet_id] ?? ['operator' => 'Unknown', 'wallet_type' => 'unknown'];
            $txn->operator = $info['operator'];
            $txn->wallet_type = $info['wallet_type'];
            return $txn;
        });

        return response()->json($results);
    }

    /**
     * Admin: Get all wallets grouped by account.
     */
    public function adminWallets(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $wallets = Wallet::select('account_id', 'operator', 'wallet_type', 'balance', 'currency', 'status')
            ->orderBy('account_id')
            ->orderBy('operator')
            ->get();

        // Group by account_id
        $grouped = $wallets->groupBy('account_id')->map(function ($accountWallets, $accountId) {
            $collection = $accountWallets->where('wallet_type', 'collection');
            $disbursement = $accountWallets->where('wallet_type', 'disbursement');
            return [
                'account_id' => $accountId,
                'collection_total' => number_format($collection->sum('balance'), 2, '.', ''),
                'disbursement_total' => number_format($disbursement->sum('balance'), 2, '.', ''),
                'overall_balance' => number_format($accountWallets->sum('balance'), 2, '.', ''),
                'wallets' => $accountWallets->values(),
            ];
        })->values();

        // Platform totals
        $totalCollection = $wallets->where('wallet_type', 'collection')->sum('balance');
        $totalDisbursement = $wallets->where('wallet_type', 'disbursement')->sum('balance');

        return response()->json([
            'accounts' => $grouped,
            'platform_collection_total' => number_format($totalCollection, 2, '.', ''),
            'platform_disbursement_total' => number_format($totalDisbursement, 2, '.', ''),
            'platform_overall_total' => number_format($totalCollection + $totalDisbursement, 2, '.', ''),
        ]);
    }

    /**
     * Admin: Fund (top-up) a business's disbursement wallet.
     */
    public function adminFund(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'account_id' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        $operator = $request->operator;
        $amount = (float) $request->amount;
        $accountId = $request->account_id;

        return DB::transaction(function () use ($operator, $amount, $accountId, $request, $user) {
            $wallet = Wallet::lockForUpdate()->firstOrCreate(
                ['account_id' => $accountId, 'operator' => $operator, 'wallet_type' => 'disbursement'],
                ['user_id' => 0, 'balance' => 0, 'currency' => 'TZS', 'status' => 'active']
            );

            if ($wallet->status !== 'active') {
                return response()->json(['message' => 'Disbursement wallet is not active.'], 403);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update(['balance' => $balanceAfter]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'reference' => 'FUND-' . strtoupper(Str::random(12)),
                'description' => $request->description ?? 'Admin fund disbursement wallet via ' . $operator,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'metadata' => json_encode([
                    'admin_fund' => true,
                    'operator' => $operator,
                    'funded_by' => $user->id,
                ]),
            ]);

            return response()->json([
                'message' => 'Disbursement wallet funded successfully.',
                'balance_before' => number_format($balanceBefore, 2, '.', ''),
                'balance_after' => number_format($balanceAfter, 2, '.', ''),
                'amount_funded' => number_format($amount, 2, '.', ''),
            ]);
        });
    }

    /**
     * Admin: Refund a collection wallet (used when settlement is rejected).
     */
    public function adminRefund(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'account_id' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        $operator = $request->operator;
        $amount = $request->amount;
        $accountId = $request->account_id;

        return DB::transaction(function () use ($operator, $amount, $accountId, $request) {
            $wallet = Wallet::lockForUpdate()
                ->where('account_id', $accountId)
                ->where('operator', $operator)
                ->where('wallet_type', 'collection')
                ->first();

            if (!$wallet) {
                return response()->json(['message' => 'Collection wallet not found.'], 404);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update(['balance' => $balanceAfter]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'reference' => 'RFND-' . strtoupper(Str::random(12)),
                'description' => $request->description ?? 'Settlement refund via ' . $operator,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'metadata' => json_encode(['refund' => true, 'operator' => $operator]),
            ]);

            return response()->json([
                'message' => 'Wallet refunded successfully.',
                'balance_after' => number_format($balanceAfter, 2, '.', ''),
            ]);
        });
    }

    /**
     * Admin: Reverse a transaction — debit collection wallet for collection reversal,
     * or credit disbursement wallet for disbursement reversal.
     */
    public function adminReverse(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'account_id' => 'required|string',
            'type' => 'required|string|in:collection,disbursement',
            'reversal_ref' => 'required|string',
            'original_ref' => 'required|string',
            'platform_charge' => 'nullable|numeric|min:0',
            'operator_charge' => 'nullable|numeric|min:0',
        ]);

        $operator = $request->operator;
        $grossAmount = (float) $request->amount;
        $accountId = $request->account_id;
        $type = $request->type;
        $platformCharge = (float) ($request->platform_charge ?? 0);
        $operatorCharge = (float) ($request->operator_charge ?? 0);
        $totalCharge = $platformCharge + $operatorCharge;
        $netAmount = $grossAmount - $totalCharge;

        if ($type === 'collection') {
            // Collection reversal: debit the collection wallet (money goes back)
            return DB::transaction(function () use ($operator, $netAmount, $grossAmount, $platformCharge, $operatorCharge, $accountId, $request) {
                $wallet = Wallet::lockForUpdate()
                    ->where('account_id', $accountId)
                    ->where('operator', $operator)
                    ->where('wallet_type', 'collection')
                    ->first();

                if (!$wallet) {
                    return response()->json(['message' => 'Collection wallet not found.'], 404);
                }

                if ($wallet->balance < $netAmount) {
                    return response()->json(['message' => 'Insufficient balance in collection wallet for reversal. Available: ' . number_format($wallet->balance, 2) . ' ' . $wallet->currency . ', Required: ' . number_format($netAmount, 2) . ' ' . $wallet->currency], 422);
                }

                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $netAmount;

                $wallet->update(['balance' => $balanceAfter]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $netAmount,
                    'reference' => $request->reversal_ref,
                    'description' => 'Collection reversal — ' . $request->original_ref . ' via ' . $operator,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'status' => 'completed',
                    'metadata' => json_encode([
                        'reversal' => true,
                        'original_ref' => $request->original_ref,
                        'gross_amount' => $grossAmount,
                        'net_amount' => $netAmount,
                        'platform_charge_refunded' => $platformCharge,
                        'operator_charge_refunded' => $operatorCharge,
                        'operator' => $operator,
                    ]),
                ]);

                return response()->json([
                    'message' => 'Collection reversal completed. Wallet debited ' . number_format($netAmount, 2) . ' ' . $wallet->currency . '.',
                    'balance_after' => number_format($balanceAfter, 2, '.', ''),
                ]);
            });
        } else {
            // Disbursement reversal: credit the disbursement wallet (money comes back)
            return DB::transaction(function () use ($operator, $netAmount, $grossAmount, $platformCharge, $operatorCharge, $accountId, $request) {
                $wallet = Wallet::lockForUpdate()
                    ->where('account_id', $accountId)
                    ->where('operator', $operator)
                    ->where('wallet_type', 'disbursement')
                    ->first();

                if (!$wallet) {
                    return response()->json(['message' => 'Disbursement wallet not found.'], 404);
                }

                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore + $netAmount;

                $wallet->update(['balance' => $balanceAfter]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $netAmount,
                    'reference' => $request->reversal_ref,
                    'description' => 'Disbursement reversal — ' . $request->original_ref . ' via ' . $operator,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'status' => 'completed',
                    'metadata' => json_encode([
                        'reversal' => true,
                        'original_ref' => $request->original_ref,
                        'gross_amount' => $grossAmount,
                        'net_amount' => $netAmount,
                        'platform_charge_refunded' => $platformCharge,
                        'operator_charge_refunded' => $operatorCharge,
                        'operator' => $operator,
                    ]),
                ]);

                return response()->json([
                    'message' => 'Disbursement reversal completed. Wallet credited ' . number_format($netAmount, 2) . ' ' . $wallet->currency . '.',
                    'balance_after' => number_format($balanceAfter, 2, '.', ''),
                ]);
            });
        }
    }

    /**
     * Helper: return wallet summary response.
     */
    private function walletSummary($user, string $message): JsonResponse
    {
        $wallets = Wallet::where('account_id', $user->account_id)->get();
        $collectionWallets = $wallets->where('wallet_type', 'collection')->values();
        $disbursementWallets = $wallets->where('wallet_type', 'disbursement')->values();

        return response()->json([
            'message' => $message,
            'collection_wallets' => $collectionWallets,
            'disbursement_wallets' => $disbursementWallets,
            'collection_total' => number_format($collectionWallets->sum('balance'), 2, '.', ''),
            'disbursement_total' => number_format($disbursementWallets->sum('balance'), 2, '.', ''),
            'overall_balance' => number_format($wallets->sum('balance'), 2, '.', ''),
        ]);
    }

    /**
     * Send webhook callback to business callback URL.
     */
    private function sendWebhook(string $token, int $accountId, array $payload): void
    {
        try {
            // Fetch account's callback URL from auth-service
            $accountRes = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(config('services.auth_service.url') . '/api/admin/accounts/' . $accountId);

            if (!$accountRes->ok()) return;

            $callbackUrl = $accountRes->json('account.callback_url');
            if (empty($callbackUrl)) return;

            // POST webhook to business callback URL
            Http::timeout(10)->post($callbackUrl, $payload);
        } catch (\Exception $e) {
            // Webhook failed silently — transaction already processed
        }
    }

    // ──────────────────────────────────────────────
    // Internal service-to-service endpoints
    // ──────────────────────────────────────────────

    /**
     * Internal: Credit a wallet (called from payment-service callbacks).
     * Accepts account_id + operator directly, no user auth needed.
     */
    public function internalCredit(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|string',
            'amount'     => 'required|numeric|min:0.01',
            'operator'   => 'required|string|in:' . implode(',', $this->operators),
            'wallet_type'=> 'nullable|string|in:collection,disbursement',
            'reference'  => 'required|string',
            'description'=> 'nullable|string|max:255',
        ]);

        $accountId = $request->account_id;
        $amount = (float) $request->amount;
        $operator = $request->operator;
        $walletType = $request->wallet_type ?? 'collection';

        return DB::transaction(function () use ($accountId, $amount, $operator, $walletType, $request) {
            $wallet = Wallet::lockForUpdate()->firstOrCreate(
                ['account_id' => $accountId, 'operator' => $operator, 'wallet_type' => $walletType],
                ['user_id' => 0, 'balance' => 0, 'currency' => 'TZS', 'status' => 'active']
            );

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update(['balance' => $balanceAfter]);

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'amount'         => $amount,
                'reference'      => $request->reference,
                'description'    => $request->description ?? "Internal credit via {$operator}",
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'status'         => 'completed',
                'metadata'       => json_encode(['internal' => true, 'operator' => $operator]),
            ]);

            return response()->json([
                'message'        => 'Wallet credited.',
                'balance_after'  => number_format($balanceAfter, 2, '.', ''),
            ]);
        });
    }

    /**
     * Internal: Debit a wallet (called from payment-service callbacks).
     * Accepts account_id + operator directly, no user auth needed.
     */
    public function internalDebit(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|string',
            'amount'     => 'required|numeric|min:0.01',
            'operator'   => 'required|string|in:' . implode(',', $this->operators),
            'wallet_type'=> 'nullable|string|in:collection,disbursement',
            'reference'  => 'required|string',
            'description'=> 'nullable|string|max:255',
        ]);

        $accountId = $request->account_id;
        $amount = (float) $request->amount;
        $operator = $request->operator;
        $walletType = $request->wallet_type ?? 'disbursement';

        return DB::transaction(function () use ($accountId, $amount, $operator, $walletType, $request) {
            $wallet = Wallet::lockForUpdate()
                ->where('account_id', $accountId)
                ->where('operator', $operator)
                ->where('wallet_type', $walletType)
                ->first();

            if (!$wallet) {
                return response()->json(['message' => "{$walletType} wallet not found for {$operator}."], 404);
            }

            if ($wallet->balance < $amount) {
                return response()->json([
                    'message' => "Insufficient {$walletType} balance for {$operator}.",
                    'available' => number_format($wallet->balance, 2, '.', ''),
                ], 422);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $wallet->update(['balance' => $balanceAfter]);

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'debit',
                'amount'         => $amount,
                'reference'      => $request->reference,
                'description'    => $request->description ?? "Internal debit via {$operator}",
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'status'         => 'completed',
                'metadata'       => json_encode(['internal' => true, 'operator' => $operator]),
            ]);

            return response()->json([
                'message'        => 'Wallet debited.',
                'balance_after'  => number_format($balanceAfter, 2, '.', ''),
            ]);
        });
    }
}
