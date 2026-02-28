<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\ChargeConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Create a transaction record (called by other services).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required',
            'transaction_ref' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|string|in:collection,disbursement,topup,settlement',
            'operator' => 'required|string',
            'status' => 'required|string|in:pending,completed,failed,cancelled,reversed',
            'platform_charge' => 'nullable|numeric|min:0',
            'operator_charge' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string',
            'operator_receipt' => 'nullable|string',
        ]);

        $user = $request->user();

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $request->account_id,
            'transaction_ref' => $request->transaction_ref,
            'amount' => $request->amount,
            'platform_charge' => $request->platform_charge ?? 0,
            'operator_charge' => $request->operator_charge ?? 0,
            'currency' => $request->currency ?? 'TZS',
            'type' => $request->type,
            'status' => $request->status,
            'description' => $request->description,
            'payment_method' => $request->payment_method ?? 'mobile_money',
            'operator' => $request->operator,
            'operator_receipt' => $request->operator_receipt,
        ]);

        return response()->json([
            'message' => 'Transaction recorded.',
            'transaction' => $transaction,
        ], 201);
    }

    /**
     * Internal: Record a transaction from another service (no user auth, service-key auth).
     */
    public function internalStore(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required',
            'transaction_ref' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|string|in:collection,disbursement,topup,settlement',
            'operator' => 'required|string',
            'status' => 'required|string|in:pending,completed,failed,cancelled,reversed',
            'platform_charge' => 'nullable|numeric|min:0',
            'operator_charge' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string',
            'operator_receipt' => 'nullable|string',
            'user_id' => 'nullable|integer',
        ]);

        $transaction = Transaction::create([
            'user_id' => $request->user_id ?? 0,
            'account_id' => $request->account_id,
            'transaction_ref' => $request->transaction_ref,
            'amount' => $request->amount,
            'platform_charge' => $request->platform_charge ?? 0,
            'operator_charge' => $request->operator_charge ?? 0,
            'currency' => $request->currency ?? 'TZS',
            'type' => $request->type,
            'status' => $request->status,
            'description' => $request->description,
            'payment_method' => $request->payment_method ?? 'mobile_money',
            'operator' => $request->operator,
            'operator_receipt' => $request->operator_receipt,
        ]);

        return response()->json([
            'message' => 'Transaction recorded.',
            'transaction' => $transaction,
        ], 201);
    }

    /**
     * Admin: Get charge revenue summary.
     */
    public function chargeRevenue(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $totalPlatformCharges = Transaction::where('status', 'completed')
            ->sum('platform_charge');

        $totalOperatorCharges = Transaction::where('status', 'completed')
            ->sum('operator_charge');

        // Per-operator breakdown
        $byOperator = Transaction::where('status', 'completed')
            ->select('operator',
                DB::raw('SUM(platform_charge) as platform_charges'),
                DB::raw('SUM(operator_charge) as operator_charges'),
                DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('operator')
            ->get();

        // Per-type breakdown
        $byType = Transaction::where('status', 'completed')
            ->select('type',
                DB::raw('SUM(platform_charge) as platform_charges'),
                DB::raw('SUM(operator_charge) as operator_charges'),
                DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('type')
            ->get();

        // Today's charges
        $todayPlatform = Transaction::where('status', 'completed')
            ->whereDate('created_at', now()->toDateString())
            ->sum('platform_charge');

        $todayOperator = Transaction::where('status', 'completed')
            ->whereDate('created_at', now()->toDateString())
            ->sum('operator_charge');

        // Per-account charges
        $byAccount = Transaction::where('status', 'completed')
            ->select('account_id',
                DB::raw('SUM(platform_charge) as platform_charges'),
                DB::raw('SUM(operator_charge) as operator_charges'),
                DB::raw('SUM(amount) as total_volume'),
                DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('account_id')
            ->get();

        return response()->json([
            'total_platform_charges' => round($totalPlatformCharges, 2),
            'total_operator_charges' => round($totalOperatorCharges, 2),
            'total_charges' => round($totalPlatformCharges + $totalOperatorCharges, 2),
            'today_platform_charges' => round($todayPlatform, 2),
            'today_operator_charges' => round($todayOperator, 2),
            'by_operator' => $byOperator,
            'by_type' => $byType,
            'by_account' => $byAccount,
        ]);
    }

    /**
     * User: Get charge summary for their account.
     */
    public function myCharges(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        $query = Transaction::where('account_id', $accountId)
            ->where('status', 'completed');

        $totalPlatformCharges = (clone $query)->sum('platform_charge');
        $totalOperatorCharges = (clone $query)->sum('operator_charge');

        $byType = (clone $query)
            ->select('type',
                DB::raw('SUM(platform_charge) as platform_charges'),
                DB::raw('SUM(operator_charge) as operator_charges'),
                DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('type')
            ->get();

        $byOperator = (clone $query)
            ->select('operator',
                DB::raw('SUM(platform_charge) as platform_charges'),
                DB::raw('SUM(operator_charge) as operator_charges'),
                DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('operator')
            ->get();

        return response()->json([
            'total_platform_charges' => round($totalPlatformCharges, 2),
            'total_charges' => round($totalPlatformCharges, 2),
            'by_type' => $byType,
            'by_operator' => $byOperator,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        $query = Transaction::query();

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_ref', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('operator_receipt', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by operator
        if ($request->filled('operator')) {
            $query->where('operator', $request->operator);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($transactions);
    }

    /**
     * Get transaction stats (status counts) for the authenticated user's account.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        $query = Transaction::query();
        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $total = (clone $query)->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $failed = (clone $query)->where('status', 'failed')->count();

        return response()->json([
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'failed' => $failed,
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $accountId = $user->account_id ?? null;

        $transaction = Transaction::where('id', $id)
            ->when($accountId, fn($q) => $q->where('account_id', $accountId))
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return response()->json(['transaction' => $transaction]);
    }

    /**
     * Admin: List ALL transactions across all accounts.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Transaction::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_ref', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('operator_receipt', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('type')) { $query->where('type', $request->type); }
        if ($request->filled('operator')) { $query->where('operator', $request->operator); }
        if ($request->filled('account_id')) { $query->where('account_id', $request->account_id); }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($transactions);
    }
}
