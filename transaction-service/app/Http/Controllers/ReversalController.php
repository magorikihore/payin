<?php

namespace App\Http\Controllers;

use App\Models\Reversal;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReversalController extends Controller
{
    /**
     * Request a reversal for a completed transaction.
     * Business users can request; admin must approve.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $accountId = $user->account_id;

        $transaction = Transaction::where('id', $request->transaction_id)
            ->where('account_id', $accountId)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'completed') {
            return response()->json(['message' => 'Only completed transactions can be reversed.'], 422);
        }

        // Check if reversal already exists for this transaction
        $existing = Reversal::where('transaction_id', $transaction->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'A reversal request already exists for this transaction.'], 422);
        }

        $reversal = Reversal::create([
            'transaction_id' => $transaction->id,
            'account_id' => $accountId,
            'reversal_ref' => 'REV-' . strtoupper(Str::random(12)),
            'original_ref' => $transaction->transaction_ref,
            'amount' => $transaction->amount,
            'platform_charge' => $transaction->platform_charge,
            'operator_charge' => $transaction->operator_charge,
            'type' => $transaction->type,
            'operator' => $transaction->operator,
            'reason' => $request->reason,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Reversal request submitted. Awaiting admin approval.',
            'reversal' => $reversal,
        ], 201);
    }

    /**
     * List reversals for the authenticated user's account.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;

        $reversals = Reversal::where('account_id', $accountId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['reversals' => $reversals]);
    }

    /**
     * Admin: List all reversals across all accounts.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Reversal::orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reversals = $query->paginate(20);

        return response()->json($reversals);
    }

    /**
     * Admin: Approve a reversal — marks transaction as reversed.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $reversal = Reversal::find($id);
        if (!$reversal) {
            return response()->json(['message' => 'Reversal not found.'], 404);
        }

        if ($reversal->status !== 'pending') {
            return response()->json(['message' => 'Reversal is already ' . $reversal->status . '.'], 422);
        }

        // Mark transaction as reversed
        $transaction = Transaction::find($reversal->transaction_id);
        if ($transaction) {
            $transaction->update(['status' => 'reversed']);
        }

        $reversal->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'message' => 'Reversal approved. Wallet service should be notified to reverse balances.',
            'reversal' => $reversal->fresh(),
        ]);
    }

    /**
     * Admin: Direct reversal — creates and auto-approves in one step.
     * Only super_admin can use this.
     */
    public function directReverse(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'transaction_id' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $transaction = Transaction::find($request->transaction_id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        if ($transaction->status !== 'completed') {
            return response()->json(['message' => 'Only completed transactions can be reversed.'], 422);
        }

        if (!in_array($transaction->type, ['collection', 'disbursement'])) {
            return response()->json(['message' => 'Only collection and disbursement transactions can be reversed.'], 422);
        }

        // Check if reversal already exists
        $existing = Reversal::where('transaction_id', $transaction->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'A reversal already exists for this transaction.'], 422);
        }

        // Create reversal as already approved
        $reversal = Reversal::create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'reversal_ref' => 'REV-' . strtoupper(Str::random(12)),
            'original_ref' => $transaction->transaction_ref,
            'amount' => $transaction->amount,
            'platform_charge' => $transaction->platform_charge,
            'operator_charge' => $transaction->operator_charge,
            'type' => $transaction->type,
            'operator' => $transaction->operator,
            'reason' => $request->reason,
            'status' => 'approved',
            'requested_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'admin_notes' => 'Direct reversal by admin',
        ]);

        // Mark transaction as reversed
        $transaction->update(['status' => 'reversed']);

        return response()->json([
            'message' => 'Transaction reversed successfully.',
            'reversal' => $reversal,
        ], 201);
    }

    /**
     * Admin: Reject a reversal.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $reversal = Reversal::find($id);
        if (!$reversal) {
            return response()->json(['message' => 'Reversal not found.'], 404);
        }

        if ($reversal->status !== 'pending') {
            return response()->json(['message' => 'Reversal is already ' . $reversal->status . '.'], 422);
        }

        $reversal->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes ?? 'Rejected',
        ]);

        return response()->json([
            'message' => 'Reversal rejected.',
            'reversal' => $reversal->fresh(),
        ]);
    }
}
