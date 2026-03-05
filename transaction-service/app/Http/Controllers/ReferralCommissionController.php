<?php

namespace App\Http\Controllers;

use App\Models\ReferralCommissionConfig;
use App\Models\ReferralEarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralCommissionController extends Controller
{
    private array $operators = ['M-Pesa', 'Tigo Pesa', 'Airtel Money', 'Halopesa', 'all'];
    private array $transactionTypes = ['collection', 'disbursement', 'all'];

    // ─── Admin: Commission Config CRUD ────────────────────────────────

    /**
     * List all referral commission configs.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = ReferralCommissionConfig::query();

        if ($request->filled('operator')) {
            $query->where('operator', $request->operator);
        }
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('referrer_account_id')) {
            if ($request->referrer_account_id === 'global') {
                $query->whereNull('referrer_account_id');
            } else {
                $query->where('referrer_account_id', $request->referrer_account_id);
            }
        }

        $configs = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['commissions' => $configs]);
    }

    /**
     * Create a referral commission config.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'referrer_account_id' => 'nullable|integer',
            'operator' => 'required|string',
            'transaction_type' => 'required|string|in:' . implode(',', $this->transactionTypes),
            'commission_type' => 'required|in:fixed,percentage,dynamic',
            'commission_value' => 'required_unless:commission_type,dynamic|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            'tiers' => 'required_if:commission_type,dynamic|array|min:1',
            'tiers.*.min_amount' => 'required_if:commission_type,dynamic|numeric|min:0',
            'tiers.*.max_amount' => 'required_if:commission_type,dynamic|numeric|min:0',
            'tiers.*.commission_type' => 'required_if:commission_type,dynamic|in:fixed,percentage',
            'tiers.*.commission_value' => 'required_if:commission_type,dynamic|numeric|min:0',
        ]);

        $config = ReferralCommissionConfig::create([
            'referrer_account_id' => $request->referrer_account_id ?: null,
            'name' => $request->name,
            'operator' => $request->operator,
            'transaction_type' => $request->transaction_type,
            'commission_type' => $request->commission_type,
            'commission_value' => $request->commission_type === 'dynamic' ? 0 : $request->commission_value,
            'tiers' => $request->commission_type === 'dynamic' ? $request->tiers : null,
            'min_amount' => $request->commission_type === 'dynamic' ? 0 : ($request->min_amount ?? 0),
            'max_amount' => $request->commission_type === 'dynamic' ? 0 : ($request->max_amount ?? 0),
            'max_commission' => $request->max_commission ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Referral commission config created successfully.',
            'commission' => $config,
        ], 201);
    }

    /**
     * Update a referral commission config.
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $config = ReferralCommissionConfig::find($id);
        if (!$config) {
            return response()->json(['message' => 'Commission config not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'referrer_account_id' => 'nullable|integer',
            'operator' => 'sometimes|string',
            'transaction_type' => 'sometimes|string|in:' . implode(',', $this->transactionTypes),
            'commission_type' => 'sometimes|in:fixed,percentage,dynamic',
            'commission_value' => 'sometimes|numeric|min:0',
            'min_amount' => 'sometimes|numeric|min:0',
            'max_amount' => 'sometimes|numeric|min:0',
            'max_commission' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'tiers' => 'nullable|array',
            'tiers.*.min_amount' => 'required_with:tiers|numeric|min:0',
            'tiers.*.max_amount' => 'required_with:tiers|numeric|min:0',
            'tiers.*.commission_type' => 'required_with:tiers|in:fixed,percentage',
            'tiers.*.commission_value' => 'required_with:tiers|numeric|min:0',
        ]);

        $updateData = $request->only([
            'name', 'operator', 'transaction_type', 'commission_type',
            'commission_value', 'min_amount', 'max_amount', 'max_commission', 'status',
        ]);
        if ($request->has('referrer_account_id')) {
            $updateData['referrer_account_id'] = $request->referrer_account_id ?: null;
        }

        $commType = $request->commission_type ?? $config->commission_type;
        if ($commType === 'dynamic') {
            $updateData['tiers'] = $request->tiers ?? $config->tiers;
            $updateData['commission_value'] = 0;
            $updateData['min_amount'] = 0;
            $updateData['max_amount'] = 0;
        } elseif ($request->has('commission_type')) {
            $updateData['tiers'] = null;
        }

        $config->update($updateData);

        return response()->json([
            'message' => 'Referral commission config updated.',
            'commission' => $config->fresh(),
        ]);
    }

    /**
     * Delete a referral commission config.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $config = ReferralCommissionConfig::find($id);
        if (!$config) {
            return response()->json(['message' => 'Commission config not found.'], 404);
        }

        $config->delete();

        return response()->json(['message' => 'Referral commission config deleted.']);
    }

    // ─── Admin: Referral Earnings ─────────────────────────────────────

    /**
     * List referral earnings (admin view).
     */
    public function earnings(Request $request): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = ReferralEarning::query();

        if ($request->filled('referrer_account_id')) {
            $query->where('referrer_account_id', $request->referrer_account_id);
        }
        if ($request->filled('referred_account_id')) {
            $query->where('referred_account_id', $request->referred_account_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = min((int) ($request->per_page ?? 50), 100);
        $earnings = $query->orderByDesc('created_at')->paginate($perPage);

        // Summary
        $summaryQuery = ReferralEarning::query();
        if ($request->filled('referrer_account_id')) {
            $summaryQuery->where('referrer_account_id', $request->referrer_account_id);
        }

        $summary = [
            'total_earned' => (float) $summaryQuery->clone()->where('status', 'credited')->sum('commission_amount'),
            'total_pending' => (float) $summaryQuery->clone()->where('status', 'pending')->sum('commission_amount'),
            'total_transactions' => $summaryQuery->clone()->count(),
        ];

        return response()->json([
            'earnings' => $earnings,
            'summary' => $summary,
        ]);
    }

    // ─── Internal: Calculate & Record Commission ──────────────────────

    /**
     * Internal service call: calculate referral commission for a transaction.
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'operator' => 'required|string',
            'transaction_type' => 'required|string',
            'referrer_account_id' => 'required|integer',
        ]);

        $operator = $this->normalizeOperator($request->operator);

        $result = ReferralCommissionConfig::calculateCommission(
            (float) $request->amount,
            $operator,
            $request->transaction_type,
            (int) $request->referrer_account_id
        );

        return response()->json($result);
    }

    /**
     * Internal service call: record a referral earning.
     */
    public function recordEarning(Request $request): JsonResponse
    {
        $request->validate([
            'referrer_account_id' => 'required|integer',
            'referred_account_id' => 'required|integer',
            'transaction_ref' => 'required|string',
            'transaction_amount' => 'required|numeric|min:0',
            'operator' => 'nullable|string',
            'transaction_type' => 'required|string',
            'commission_type' => 'required|in:fixed,percentage,dynamic',
            'commission_rate' => 'required|numeric|min:0',
            'commission_amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:pending,credited,failed',
            'wallet_reference' => 'nullable|string',
        ]);

        $earning = ReferralEarning::create([
            'referrer_account_id' => $request->referrer_account_id,
            'referred_account_id' => $request->referred_account_id,
            'transaction_ref' => $request->transaction_ref,
            'transaction_amount' => $request->transaction_amount,
            'operator' => $request->operator,
            'transaction_type' => $request->transaction_type,
            'commission_type' => $request->commission_type,
            'commission_rate' => $request->commission_rate,
            'commission_amount' => $request->commission_amount,
            'status' => $request->status ?? 'pending',
            'wallet_reference' => $request->wallet_reference,
        ]);

        return response()->json([
            'message' => 'Referral earning recorded.',
            'earning' => $earning,
        ], 201);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    private function normalizeOperator(string $operator): string
    {
        $codeToName = [
            'mpesa' => 'M-Pesa', 'm-pesa' => 'M-Pesa',
            'tigopesa' => 'Tigo Pesa', 'tigo pesa' => 'Tigo Pesa',
            'airtelmoney' => 'Airtel Money', 'airtel' => 'Airtel Money', 'airtel money' => 'Airtel Money',
            'halopesa' => 'Halopesa', 'halotel' => 'Halopesa',
            'all' => 'all',
        ];
        return $codeToName[strtolower($operator)] ?? $operator;
    }

    private function isSuperAdmin(Request $request): bool
    {
        $user = $request->user();
        return $user && in_array($user->role ?? null, ['super_admin', 'admin_user']);
    }
}
