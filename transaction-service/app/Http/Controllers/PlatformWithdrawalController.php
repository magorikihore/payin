<?php

namespace App\Http\Controllers;

use App\Models\PlatformWithdrawal;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlatformWithdrawalController extends Controller
{
    /**
     * Get profit summary: total earned, total withdrawn, available balance.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $totalEarned = Transaction::where('status', 'completed')
            ->sum('platform_charge');

        $totalWithdrawn = PlatformWithdrawal::whereIn('status', ['pending', 'completed'])
            ->sum('amount');

        $totalCompleted = PlatformWithdrawal::where('status', 'completed')
            ->sum('amount');

        $totalPending = PlatformWithdrawal::where('status', 'pending')
            ->sum('amount');

        $availableBalance = round($totalEarned - $totalWithdrawn, 2);

        return response()->json([
            'total_earned' => round($totalEarned, 2),
            'total_withdrawn' => round($totalCompleted, 2),
            'total_pending' => round($totalPending, 2),
            'available_balance' => $availableBalance,
        ]);
    }

    /**
     * List all platform withdrawals (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = PlatformWithdrawal::query()->orderByDesc('created_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $withdrawals = $query->paginate($request->query('per_page', 20));

        return response()->json($withdrawals);
    }

    /**
     * Create a new platform withdrawal request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Only super admins can create withdrawals.'], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        // Check available balance
        $totalEarned = Transaction::where('status', 'completed')->sum('platform_charge');
        $totalWithdrawn = PlatformWithdrawal::whereIn('status', ['pending', 'completed'])->sum('amount');
        $available = round($totalEarned - $totalWithdrawn, 2);

        if ($validated['amount'] > $available) {
            return response()->json([
                'message' => 'Insufficient profit balance. Available: ' . number_format($available, 2) . ' TZS',
            ], 422);
        }

        $withdrawal = PlatformWithdrawal::create([
            'reference' => 'PW-' . strtoupper(Str::random(12)),
            'amount' => $validated['amount'],
            'currency' => 'TZS',
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'branch' => $validated['branch'] ?? null,
            'swift_code' => $validated['swift_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Withdrawal request created successfully.',
            'withdrawal' => $withdrawal,
        ], 201);
    }

    /**
     * Mark withdrawal as completed.
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Only super admins can complete withdrawals.'], 403);
        }

        $withdrawal = PlatformWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Withdrawal is already ' . $withdrawal->status . '.'], 422);
        }

        $withdrawal->update([
            'status' => 'completed',
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Withdrawal marked as completed.',
            'withdrawal' => $withdrawal->fresh(),
        ]);
    }

    /**
     * Cancel a pending withdrawal.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Only super admins can cancel withdrawals.'], 403);
        }

        $withdrawal = PlatformWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Withdrawal is already ' . $withdrawal->status . '.'], 422);
        }

        $withdrawal->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Withdrawal cancelled. Funds returned to available balance.',
            'withdrawal' => $withdrawal->fresh(),
        ]);
    }
}
