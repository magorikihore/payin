<?php

namespace App\Http\Controllers;

use App\Models\Settlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SettlementController extends Controller
{
    private array $operators = ['M-Pesa', 'Tigo Pesa', 'Airtel Money', 'Halopesa'];

    /**
     * List settlements for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Settlement::where('account_id', $user->account_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('settlement_ref', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Create a settlement request - debits from disbursement wallet.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'bank_account_id' => 'required|integer',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $token = $request->bearerToken();

        // Fetch bank account details from auth-service
        $bankName = '';
        $accountNumber = '';
        $accountName = '';

        try {
            $bankRes = Http::get(config('services.auth_service.url') . '/api/internal/bank-accounts/' . $user->account_id);
            if ($bankRes->ok()) {
                $bankAccounts = $bankRes->json('bank_accounts') ?? [];
                $selected = collect($bankAccounts)->firstWhere('id', $request->bank_account_id);
                if (!$selected) {
                    return response()->json(['message' => 'Selected bank account not found.'], 422);
                }
                $bankName = $selected['bank_name'];
                $accountNumber = $selected['account_number'];
                $accountName = $selected['account_name'];
            } else {
                return response()->json(['message' => 'Unable to verify bank account.'], 503);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Auth service unavailable. Cannot verify bank account.'], 503);
        }

        $operator = $request->operator;
        $settlementRef = 'STL-' . strtoupper(Str::random(12));
        $settlementAmount = (float) $request->amount;
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
                'amount' => $settlementAmount,
                'operator' => $operator,
                'transaction_type' => 'settlement',
                'account_id' => $user->account_id,
            ]);

            if ($chargeRes->ok()) {
                $chargeData = $chargeRes->json();
                $platformCharge = (float) ($chargeData['platform_charge'] ?? 0);
                $operatorCharge = (float) ($chargeData['operator_charge'] ?? 0);
                $totalCharge = (float) ($chargeData['total_charge'] ?? 0);
            }
        } catch (\Exception $e) {
            // Proceed without charges if service unavailable
        }

        // Total to debit = settlement amount + platform charge only
        // Operator charge is our cost, deducted from platform profit
        $totalDebit = $settlementAmount + $platformCharge;

        // Call wallet-service to debit the disbursement wallet
        $walletUrl = config('services.wallet_service.url') . '/api/wallet/debit-settlement';

        try {
            $walletResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post($walletUrl, [
                'amount' => $totalDebit,
                'operator' => $operator,
                'settlement_ref' => $settlementRef,
                'description' => 'Settlement: ' . ($request->description ?? $settlementRef) . ($totalCharge > 0 ? " (incl. charges: " . number_format($totalCharge, 2) . " {$currency})" : ''),
            ]);

            if ($walletResponse->failed()) {
                $errorMsg = $walletResponse->json('message') ?? 'Failed to debit disbursement wallet.';
                return response()->json(['message' => $errorMsg], $walletResponse->status());
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Wallet service unavailable. Cannot process settlement.'], 503);
        }

        // Record transaction in transaction-service
        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post(config('services.transaction_service.url') . '/api/transactions', [
                'account_id' => $user->account_id,
                'transaction_ref' => $settlementRef,
                'amount' => $settlementAmount,
                'type' => 'settlement',
                'operator' => $operator,
                'status' => 'completed',
                'platform_charge' => $platformCharge,
                'operator_charge' => $operatorCharge,
                'currency' => $currency,
                'description' => 'Settlement to ' . $bankName . ' - ' . $accountName,
                'payment_method' => 'bank_transfer',
            ]);
        } catch (\Exception $e) {
            // Log silently - settlement was already created
        }

        // Wallet debited successfully, create the settlement record
        $settlement = Settlement::create([
            'user_id' => $user->id,
            'account_id' => $user->account_id,
            'settlement_ref' => $settlementRef,
            'amount' => $settlementAmount,
            'currency' => $currency,
            'operator' => $operator,
            'status' => 'pending',
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'description' => $request->description ?? 'Settlement request',
            'metadata' => json_encode([
                'platform_charge' => $platformCharge,
                'operator_charge' => $operatorCharge,
                'total_charge' => $totalCharge,
                'total_debited' => $totalDebit,
            ]),
        ]);

        $chargeMsg = $totalCharge > 0 ? " Charges: " . number_format($totalCharge, 2) . " {$currency}." : '';

        // Send webhook callback for payout created
        $this->sendWebhook($token, $user->account_id, [
            'event' => 'payout.created',
            'settlement_ref' => $settlementRef,
            'type' => 'settlement',
            'operator' => $operator,
            'amount' => $settlementAmount,
            'platform_charge' => $platformCharge,
            'operator_charge' => $operatorCharge,
            'total_debited' => $totalDebit,
            'currency' => $currency,
            'status' => 'pending',
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'description' => $request->description ?? 'Settlement request',
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => "Settlement request created. " . number_format($totalDebit, 2) . " {$currency} debited from {$operator} disbursement wallet (Amount: " . number_format($settlementAmount, 2) . " {$currency}{$chargeMsg}).",
            'settlement' => $settlement,
            'charges' => [
                'platform_charge' => number_format($platformCharge, 2, '.', ''),
                'operator_charge' => number_format($operatorCharge, 2, '.', ''),
                'total_charge' => number_format($totalCharge, 2, '.', ''),
                'total_debited' => number_format($totalDebit, 2, '.', ''),
            ],
        ], 201);
    }

    /**
     * Show a specific settlement.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $settlement = Settlement::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (!$settlement) {
            return response()->json(['message' => 'Settlement not found.'], 404);
        }

        return response()->json(['settlement' => $settlement]);
    }

    /**
     * Admin: List ALL settlements across all accounts.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Settlement::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('settlement_ref', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
            });
        }
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Admin: Approve a settlement (status pending → approved).
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $settlement = Settlement::find($id);
        if (!$settlement) {
            return response()->json(['message' => 'Settlement not found.'], 404);
        }
        if ($settlement->status !== 'pending') {
            return response()->json(['message' => 'Settlement is not pending. Current status: ' . $settlement->status], 422);
        }

        $settlement->update(['status' => 'approved']);

        // Send webhook callback for payout approved
        $this->sendWebhook($request->bearerToken(), $settlement->account_id, [
            'event' => 'payout.approved',
            'settlement_ref' => $settlement->settlement_ref,
            'type' => 'settlement',
            'operator' => $settlement->operator,
            'amount' => (float) $settlement->amount,
            'currency' => $settlement->currency,
            'status' => 'approved',
            'bank_name' => $settlement->bank_name,
            'account_number' => $settlement->account_number,
            'account_name' => $settlement->account_name,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Notify account owner via auth-service
        try {
            Http::post(config('services.auth_service.url') . '/api/internal/send-notification', [
                'account_id' => $settlement->account_id,
                'type' => 'settlement_approved',
                'data' => [
                    'settlement_ref' => $settlement->settlement_ref,
                    'operator' => $settlement->operator,
                    'amount' => $settlement->amount,
                    'currency' => $settlement->currency,
                    'bank_name' => $settlement->bank_name,
                    'account_number' => $settlement->account_number,
                    'account_name' => $settlement->account_name,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Settlement approval notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Settlement approved. Ref: ' . $settlement->settlement_ref,
            'settlement' => $settlement->fresh(),
        ]);
    }

    /**
     * Admin: Reject a settlement (refund wallet, status → rejected).
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $settlement = Settlement::find($id);
        if (!$settlement) {
            return response()->json(['message' => 'Settlement not found.'], 404);
        }
        if ($settlement->status !== 'pending') {
            return response()->json(['message' => 'Settlement is not pending. Current status: ' . $settlement->status], 422);
        }

        // Refund: credit back the collection wallet via wallet-service
        $token = $request->bearerToken();
        try {
            $walletUrl = config('services.wallet_service.url') . '/api/admin/wallet/refund';
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->post($walletUrl, [
                'amount' => $settlement->amount,
                'operator' => $settlement->operator,
                'account_id' => $settlement->account_id,
                'description' => 'Settlement rejected - refund: ' . $settlement->settlement_ref,
            ]);
        } catch (\Exception $e) {
            // Log but proceed with rejection — admin can manually reconcile
        }

        $settlement->update(['status' => 'rejected']);

        // Send webhook callback for payout rejected
        $this->sendWebhook($request->bearerToken(), $settlement->account_id, [
            'event' => 'payout.rejected',
            'settlement_ref' => $settlement->settlement_ref,
            'type' => 'settlement',
            'operator' => $settlement->operator,
            'amount' => (float) $settlement->amount,
            'currency' => $settlement->currency,
            'status' => 'rejected',
            'bank_name' => $settlement->bank_name,
            'account_number' => $settlement->account_number,
            'account_name' => $settlement->account_name,
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => 'Settlement rejected. Ref: ' . $settlement->settlement_ref,
            'settlement' => $settlement->fresh(),
        ]);
    }

    /**
     * Send webhook callback to business callback URL.
     */
    private function sendWebhook(string $token, int $accountId, array $payload): void
    {
        try {
            $accountRes = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(config('services.auth_service.url') . '/api/admin/accounts/' . $accountId);

            if (!$accountRes->ok()) return;

            $callbackUrl = $accountRes->json('account.callback_url');
            if (empty($callbackUrl)) return;

            Http::timeout(10)->post($callbackUrl, $payload);
        } catch (\Exception $e) {
            // Webhook failed silently
        }
    }
}
