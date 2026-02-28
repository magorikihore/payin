<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * List bank accounts for the authenticated user's account.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $accounts = BankAccount::where('account_id', $user->account_id)
            ->orderByDesc('is_default')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['bank_accounts' => $accounts]);
    }

    /**
     * Add a new bank account.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only owner or admin can manage bank accounts.'], 403);
        }

        $request->validate([
            'bank_name' => 'required|string|max:191',
            'account_name' => 'required|string|max:191',
            'account_number' => 'required|string|max:191',
            'swift_code' => 'nullable|string|max:191',
            'branch' => 'nullable|string|max:191',
            'label' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        $accountId = $user->account_id;

        // If this is the first bank account or marked as default, set it as default
        $isDefault = $request->boolean('is_default', false);
        $existingCount = BankAccount::where('account_id', $accountId)->count();

        if ($existingCount === 0) {
            $isDefault = true;
        }

        // If marking as default, unset other defaults
        if ($isDefault) {
            BankAccount::where('account_id', $accountId)->update(['is_default' => false]);
        }

        $bankAccount = BankAccount::create([
            'account_id' => $accountId,
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'swift_code' => $request->swift_code,
            'branch' => $request->branch,
            'label' => $request->label,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'message' => 'Bank account added.',
            'bank_account' => $bankAccount,
        ], 201);
    }

    /**
     * Set a bank account as default.
     */
    public function setDefault(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only owner or admin can manage bank accounts.'], 403);
        }

        $bankAccount = BankAccount::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (!$bankAccount) {
            return response()->json(['message' => 'Bank account not found.'], 404);
        }

        // Unset all defaults for this account
        BankAccount::where('account_id', $user->account_id)->update(['is_default' => false]);

        $bankAccount->update(['is_default' => true]);

        return response()->json(['message' => 'Default bank account updated.', 'bank_account' => $bankAccount]);
    }

    /**
     * Delete a bank account.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only owner or admin can manage bank accounts.'], 403);
        }

        $bankAccount = BankAccount::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (!$bankAccount) {
            return response()->json(['message' => 'Bank account not found.'], 404);
        }

        $wasDefault = $bankAccount->is_default;
        $bankAccount->delete();

        // If deleted was default, set the first remaining as default
        if ($wasDefault) {
            $first = BankAccount::where('account_id', $user->account_id)->first();
            if ($first) {
                $first->update(['is_default' => true]);
            }
        }

        return response()->json(['message' => 'Bank account removed.']);
    }

    /**
     * Internal: Get bank accounts for an account (called by other services).
     */
    public function internalIndex(Request $request, $accountId): JsonResponse
    {
        $accounts = BankAccount::where('account_id', $accountId)
            ->orderByDesc('is_default')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['bank_accounts' => $accounts]);
    }

    /**
     * Internal: Create a bank account for an account (called by admin create-business).
     */
    public function internalStore(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|integer',
            'bank_name' => 'required|string|max:191',
            'account_name' => 'required|string|max:191',
            'account_number' => 'required|string|max:191',
            'swift_code' => 'nullable|string|max:191',
            'branch' => 'nullable|string|max:191',
            'is_default' => 'nullable|boolean',
        ]);

        $bankAccount = BankAccount::create([
            'account_id' => $request->account_id,
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'swift_code' => $request->swift_code,
            'branch' => $request->branch,
            'is_default' => $request->boolean('is_default', true),
        ]);

        return response()->json(['message' => 'Bank account created.', 'bank_account' => $bankAccount], 201);
    }
}
