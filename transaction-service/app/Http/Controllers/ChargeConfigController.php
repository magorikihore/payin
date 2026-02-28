<?php

namespace App\Http\Controllers;

use App\Models\ChargeConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChargeConfigController extends Controller
{
    private array $operators = ['M-Pesa', 'Tigo Pesa', 'Airtel Money', 'Halopesa', 'all'];
    private array $transactionTypes = ['collection', 'disbursement', 'topup', 'settlement', 'all'];

    /**
     * List all charge configs (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized. Super admin only.'], 403);
        }

        $query = ChargeConfig::query();

        if ($request->filled('operator')) {
            $query->where('operator', $request->operator);
        }
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('account_id')) {
            if ($request->account_id === 'global') {
                $query->whereNull('account_id');
            } else {
                $query->where('account_id', $request->account_id);
            }
        }

        $charges = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['charges' => $charges]);
    }

    /**
     * Create a charge config (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized. Super admin only.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'account_id' => 'nullable|integer',
            'operator' => 'required|string|in:' . implode(',', $this->operators),
            'transaction_type' => 'required|string|in:' . implode(',', $this->transactionTypes),
            'charge_type' => 'required|in:fixed,percentage,dynamic',
            'charge_value' => 'required_unless:charge_type,dynamic|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'applies_to' => 'required|in:platform,operator',
            'status' => 'nullable|in:active,inactive',
            'tiers' => 'required_if:charge_type,dynamic|array|min:1',
            'tiers.*.min_amount' => 'required_if:charge_type,dynamic|numeric|min:0',
            'tiers.*.max_amount' => 'required_if:charge_type,dynamic|numeric|min:0',
            'tiers.*.charge_type' => 'required_if:charge_type,dynamic|in:fixed,percentage',
            'tiers.*.charge_value' => 'required_if:charge_type,dynamic|numeric|min:0',
        ]);

        $charge = ChargeConfig::create([
            'account_id' => $request->account_id ?: null,
            'name' => $request->name,
            'operator' => $request->operator,
            'transaction_type' => $request->transaction_type,
            'charge_type' => $request->charge_type,
            'charge_value' => $request->charge_type === 'dynamic' ? 0 : $request->charge_value,
            'tiers' => $request->charge_type === 'dynamic' ? $request->tiers : null,
            'min_amount' => $request->charge_type === 'dynamic' ? 0 : ($request->min_amount ?? 0),
            'max_amount' => $request->charge_type === 'dynamic' ? 0 : ($request->max_amount ?? 0),
            'applies_to' => $request->applies_to,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Charge configuration created successfully.',
            'charge' => $charge,
        ], 201);
    }

    /**
     * Update a charge config (admin only).
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized. Super admin only.'], 403);
        }

        $charge = ChargeConfig::find($id);
        if (!$charge) {
            return response()->json(['message' => 'Charge config not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'account_id' => 'nullable|integer',
            'operator' => 'sometimes|string|in:' . implode(',', $this->operators),
            'transaction_type' => 'sometimes|string|in:' . implode(',', $this->transactionTypes),
            'charge_type' => 'sometimes|in:fixed,percentage,dynamic',
            'charge_value' => 'sometimes|numeric|min:0',
            'min_amount' => 'sometimes|numeric|min:0',
            'max_amount' => 'sometimes|numeric|min:0',
            'applies_to' => 'sometimes|in:platform,operator',
            'status' => 'sometimes|in:active,inactive',
            'tiers' => 'nullable|array',
            'tiers.*.min_amount' => 'required_with:tiers|numeric|min:0',
            'tiers.*.max_amount' => 'required_with:tiers|numeric|min:0',
            'tiers.*.charge_type' => 'required_with:tiers|in:fixed,percentage',
            'tiers.*.charge_value' => 'required_with:tiers|numeric|min:0',
        ]);

        $updateData = $request->only([
            'name', 'operator', 'transaction_type', 'charge_type',
            'charge_value', 'min_amount', 'max_amount', 'applies_to', 'status',
        ]);
        if ($request->has('account_id')) {
            $updateData['account_id'] = $request->account_id ?: null;
        }
        // Handle tiers for dynamic charge type
        $chargeType = $request->charge_type ?? $charge->charge_type;
        if ($chargeType === 'dynamic') {
            $updateData['tiers'] = $request->tiers ?? $charge->tiers;
            $updateData['charge_value'] = 0;
            $updateData['min_amount'] = 0;
            $updateData['max_amount'] = 0;
        } elseif ($request->has('charge_type')) {
            $updateData['tiers'] = null;
        }
        $charge->update($updateData);

        return response()->json([
            'message' => 'Charge configuration updated.',
            'charge' => $charge->fresh(),
        ]);
    }

    /**
     * Delete a charge config (admin only).
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        if (!$this->isSuperAdmin($request)) {
            return response()->json(['message' => 'Unauthorized. Super admin only.'], 403);
        }

        $charge = ChargeConfig::find($id);
        if (!$charge) {
            return response()->json(['message' => 'Charge config not found.'], 404);
        }

        $charge->delete();

        return response()->json(['message' => 'Charge configuration deleted.']);
    }

    /**
     * Calculate charges for a given amount/operator/type (public to authenticated users).
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'operator' => 'required|string',
            'transaction_type' => 'required|string|in:' . implode(',', array_diff($this->transactionTypes, ['all'])),
            'account_id' => 'nullable|integer',
        ]);

        $charges = ChargeConfig::calculateCharges(
            (float) $request->amount,
            $request->operator,
            $request->transaction_type,
            $request->account_id
        );

        return response()->json($charges);
    }

    private function isSuperAdmin(Request $request): bool
    {
        $user = $request->user();
        return $user && in_array($user->role ?? null, ['super_admin', 'admin_user']);
    }
}
