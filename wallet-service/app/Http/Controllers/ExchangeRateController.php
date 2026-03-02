<?php

namespace App\Http\Controllers;

use App\Models\CurrencyExchange;
use App\Models\ExchangeRate;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExchangeRateController extends Controller
{
    /**
     * Supported currencies across all regions.
     */
    private array $currencies = [
        'TZS' => 'Tanzanian Shilling',
        'KES' => 'Kenyan Shilling',
        'UGX' => 'Ugandan Shilling',
        'RWF' => 'Rwandan Franc',
        'BIF' => 'Burundian Franc',
        'CDF' => 'Congolese Franc',
        'MZN' => 'Mozambican Metical',
        'MWK' => 'Malawian Kwacha',
        'ZMW' => 'Zambian Kwacha',
        'ZAR' => 'South African Rand',
        'NGN' => 'Nigerian Naira',
        'GHS' => 'Ghanaian Cedi',
        'USD' => 'US Dollar',
    ];

    // ──────────── Admin endpoints ────────────

    /**
     * List all exchange rates.
     */
    public function index(Request $request): JsonResponse
    {
        $rates = ExchangeRate::orderBy('from_currency')->orderBy('to_currency')->get();

        return response()->json([
            'rates' => $rates,
            'currencies' => $this->currencies,
        ]);
    }

    /**
     * Create or update an exchange rate pair.
     */
    public function upsert(Request $request): JsonResponse
    {
        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'buy_rate' => 'required|numeric|min:0.000001',
            'sell_rate' => 'required|numeric|min:0.000001',
            'conversion_fee_percent' => 'required|numeric|min:0|max:50',
            'is_active' => 'boolean',
        ]);

        $from = strtoupper($request->from_currency);
        $to = strtoupper($request->to_currency);

        $rate = ExchangeRate::updateOrCreate(
            ['from_currency' => $from, 'to_currency' => $to],
            [
                'buy_rate' => $request->buy_rate,
                'sell_rate' => $request->sell_rate,
                'conversion_fee_percent' => $request->conversion_fee_percent,
                'is_active' => $request->is_active ?? true,
            ]
        );

        return response()->json([
            'message' => "Exchange rate {$from} → {$to} saved.",
            'rate' => $rate,
        ]);
    }

    /**
     * Toggle an exchange rate active/inactive.
     */
    public function toggle(Request $request, $id): JsonResponse
    {
        $rate = ExchangeRate::findOrFail($id);
        $rate->update(['is_active' => !$rate->is_active]);

        $status = $rate->is_active ? 'activated' : 'deactivated';
        return response()->json([
            'message' => "{$rate->from_currency} → {$rate->to_currency} {$status}.",
            'rate' => $rate,
        ]);
    }

    /**
     * Delete an exchange rate.
     */
    public function destroy($id): JsonResponse
    {
        $rate = ExchangeRate::findOrFail($id);
        $pair = "{$rate->from_currency} → {$rate->to_currency}";
        $rate->delete();

        return response()->json(['message' => "Exchange rate {$pair} deleted."]);
    }

    /**
     * Get exchange history (admin view).
     */
    public function adminExchangeHistory(Request $request): JsonResponse
    {
        $query = CurrencyExchange::orderBy('created_at', 'desc');

        if ($request->account_id) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->from_currency) {
            $query->where('from_currency', $request->from_currency);
        }
        if ($request->to_currency) {
            $query->where('to_currency', $request->to_currency);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $exchanges = $query->paginate(20);

        // Total platform revenue
        $totalRevenue = CurrencyExchange::where('status', 'completed')->sum('platform_revenue');

        return response()->json([
            'exchanges' => $exchanges,
            'total_platform_revenue' => number_format((float) $totalRevenue, 2, '.', ''),
        ]);
    }

    // ──────────── User endpoints ────────────

    /**
     * Get available exchange rates for the user's account currencies.
     */
    public function availableRates(Request $request): JsonResponse
    {
        $user = $request->user();
        $account = $user->account ?? null;

        if (!$account || empty($account['multi_currency_enabled'])) {
            return response()->json([
                'message' => 'Multi-currency is not enabled for your account.',
                'rates' => [],
                'allowed_currencies' => [],
            ]);
        }

        $allowedCurrencies = $account['allowed_currencies'] ?? [$account['currency'] ?? 'TZS'];
        $baseCurrency = $account['currency'] ?? 'TZS';

        // Include base currency
        if (!in_array($baseCurrency, $allowedCurrencies)) {
            array_unshift($allowedCurrencies, $baseCurrency);
        }

        $rates = ExchangeRate::where('is_active', true)
            ->where(function ($q) use ($allowedCurrencies) {
                $q->whereIn('from_currency', $allowedCurrencies)
                    ->whereIn('to_currency', $allowedCurrencies);
            })
            ->get();

        return response()->json([
            'rates' => $rates,
            'allowed_currencies' => $allowedCurrencies,
            'base_currency' => $baseCurrency,
            'currencies' => collect($this->currencies)->only($allowedCurrencies),
        ]);
    }

    /**
     * Preview a currency exchange (no commit).
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'amount' => 'required|numeric|min:1',
            'from_operator' => 'required|string',
            'from_wallet_type' => 'required|in:collection,disbursement',
        ]);

        $user = $request->user();
        $account = $user->account ?? null;

        if (!$account || empty($account['multi_currency_enabled'])) {
            return response()->json(['message' => 'Multi-currency is not enabled for your account.'], 403);
        }

        $from = strtoupper($request->from_currency);
        $to = strtoupper($request->to_currency);

        $rate = ExchangeRate::findRate($from, $to);
        if (!$rate) {
            return response()->json(['message' => "No active exchange rate for {$from} → {$to}."], 404);
        }

        $calc = $rate->calculate((float) $request->amount);

        return response()->json([
            'from_currency' => $from,
            'to_currency' => $to,
            'from_amount' => number_format((float) $request->amount, 2, '.', ''),
            'to_amount' => number_format($calc['to_amount'], 2, '.', ''),
            'rate' => $calc['rate_applied'],
            'fee_percent' => $calc['fee_percent'],
            'fee_amount' => number_format($calc['fee_amount'], 2, '.', ''),
        ]);
    }

    /**
     * Execute a currency exchange.
     */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'amount' => 'required|numeric|min:1',
            'from_operator' => 'required|string',
            'from_wallet_type' => 'required|in:collection,disbursement',
            'to_operator' => 'required|string',
            'to_wallet_type' => 'required|in:collection,disbursement',
        ]);

        $user = $request->user();
        $account = $user->account ?? null;

        if (!$account || empty($account['multi_currency_enabled'])) {
            return response()->json(['message' => 'Multi-currency is not enabled for your account.'], 403);
        }

        $accountId = $user->account_id;
        $from = strtoupper($request->from_currency);
        $to = strtoupper($request->to_currency);
        $amount = (float) $request->amount;

        // Verify currencies are allowed
        $allowed = $account['allowed_currencies'] ?? [$account['currency'] ?? 'TZS'];
        $baseCurrency = $account['currency'] ?? 'TZS';
        if (!in_array($baseCurrency, $allowed)) {
            $allowed[] = $baseCurrency;
        }

        if (!in_array($from, $allowed) || !in_array($to, $allowed)) {
            return response()->json(['message' => 'One or both currencies are not allowed for your account.'], 403);
        }

        $rate = ExchangeRate::findRate($from, $to);
        if (!$rate) {
            return response()->json(['message' => "No active exchange rate for {$from} → {$to}."], 404);
        }

        $calc = $rate->calculate($amount);

        return DB::transaction(function () use ($user, $accountId, $from, $to, $amount, $calc, $request, $rate) {
            // Find source wallet
            $sourceWallet = Wallet::where('account_id', $accountId)
                ->where('operator', $request->from_operator)
                ->where('wallet_type', $request->from_wallet_type)
                ->where('currency', $from)
                ->lockForUpdate()
                ->first();

            if (!$sourceWallet || $sourceWallet->balance < $amount) {
                return response()->json([
                    'message' => 'Insufficient balance in source wallet.',
                    'available' => $sourceWallet ? number_format((float) $sourceWallet->balance, 2, '.', '') : '0.00',
                ], 422);
            }

            // Find or create destination wallet
            $destWallet = Wallet::lockForUpdate()->firstOrCreate(
                [
                    'account_id' => $accountId,
                    'operator' => $request->to_operator,
                    'wallet_type' => $request->to_wallet_type,
                    'currency' => $to,
                ],
                [
                    'user_id' => $user->id,
                    'balance' => 0,
                    'status' => 'active',
                ]
            );

            $ref = 'FX-' . strtoupper(Str::random(12));

            // Debit source wallet
            $srcBefore = (float) $sourceWallet->balance;
            $sourceWallet->decrement('balance', $amount);
            $srcAfter = (float) $sourceWallet->fresh()->balance;

            WalletTransaction::create([
                'wallet_id' => $sourceWallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $ref . '-SRC',
                'description' => "Currency exchange: {$from} → {$to} ({$request->from_operator} → {$request->to_operator})",
                'balance_before' => $srcBefore,
                'balance_after' => $srcAfter,
                'status' => 'completed',
                'metadata' => json_encode(['exchange_ref' => $ref, 'type' => 'currency_exchange', 'direction' => 'debit']),
            ]);

            // Credit destination wallet
            $destBefore = (float) $destWallet->balance;
            $destWallet->increment('balance', $calc['to_amount']);
            $destAfter = (float) $destWallet->fresh()->balance;

            WalletTransaction::create([
                'wallet_id' => $destWallet->id,
                'type' => 'credit',
                'amount' => $calc['to_amount'],
                'reference' => $ref . '-DST',
                'description' => "Currency exchange: {$from} → {$to} ({$request->from_operator} → {$request->to_operator})",
                'balance_before' => $destBefore,
                'balance_after' => $destAfter,
                'status' => 'completed',
                'metadata' => json_encode(['exchange_ref' => $ref, 'type' => 'currency_exchange', 'direction' => 'credit']),
            ]);

            // Log the exchange
            $exchange = CurrencyExchange::create([
                'account_id' => $user->account_id,
                'user_id' => $user->id,
                'reference' => $ref,
                'from_currency' => $from,
                'to_currency' => $to,
                'from_amount' => $amount,
                'to_amount' => $calc['to_amount'],
                'rate_applied' => $calc['rate_applied'],
                'fee_percent' => $calc['fee_percent'],
                'fee_amount' => $calc['fee_amount'],
                'platform_revenue' => $calc['platform_revenue'],
                'from_operator' => $request->from_operator,
                'to_operator' => $request->to_operator,
                'from_wallet_type' => $request->from_wallet_type,
                'to_wallet_type' => $request->to_wallet_type,
                'status' => 'completed',
            ]);

            return response()->json([
                'message' => "Exchanged " . number_format($amount, 2) . " {$from} → " . number_format($calc['to_amount'], 2) . " {$to}. Fee: " . number_format($calc['fee_amount'], 2) . " {$from} ({$calc['fee_percent']}%).",
                'exchange' => $exchange,
                'source_wallet_balance' => number_format($srcAfter, 2, '.', ''),
                'destination_wallet_balance' => number_format($destAfter, 2, '.', ''),
            ]);
        });
    }

    /**
     * User's exchange history.
     */
    public function myExchanges(Request $request): JsonResponse
    {
        $exchanges = CurrencyExchange::where('account_id', $request->user()->account_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($exchanges);
    }
}
