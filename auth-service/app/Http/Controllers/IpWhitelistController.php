<?php

namespace App\Http\Controllers;

use App\Models\IpWhitelist;
use App\Models\User;
use App\Notifications\IpWhitelistApprovedNotification;
use App\Notifications\AdminIpWhitelistRequestedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpWhitelistController extends Controller
{
    /**
     * List IPs for the authenticated user's account.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $ips = IpWhitelist::where('account_id', $user->account_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['ips' => $ips]);
    }

    /**
     * Add a new IP to whitelist (pending admin approval).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only account owner or admin can add IPs.'], 403);
        }

        $request->validate([
            'ip_address' => 'required|ip|max:45',
            'label' => 'nullable|string|max:100',
        ]);

        // Check for duplicates
        $existing = IpWhitelist::where('account_id', $user->account_id)
            ->where('ip_address', $request->ip_address)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'This IP address is already in the whitelist.'], 422);
        }

        $ip = IpWhitelist::create([
            'account_id' => $user->account_id,
            'ip_address' => $request->ip_address,
            'label' => $request->label,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        // Notify admin users about new IP whitelist request
        try {
            $account = $user->account;
            $admins = User::whereIn('role', ['super_admin', 'admin_user'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new AdminIpWhitelistRequestedNotification([
                    'business_name' => $account->business_name ?? 'N/A',
                    'ip_address' => $request->ip_address,
                    'label' => $request->label ?? 'N/A',
                    'requested_by' => $user->firstname . ' ' . $user->lastname,
                ]));
            }
        } catch (\Throwable $e) {
            \Log::warning('Admin IP whitelist notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'IP address added. Pending admin approval.',
            'ip' => $ip,
        ], 201);
    }

    /**
     * Delete an IP from whitelist.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only account owner or admin can remove IPs.'], 403);
        }

        $ip = IpWhitelist::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (!$ip) {
            return response()->json(['message' => 'IP not found.'], 404);
        }

        $ip->delete();

        return response()->json(['message' => 'IP address removed from whitelist.']);
    }

    /**
     * Admin: List all pending/all IP whitelist requests.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = IpWhitelist::with(['account:id,business_name,account_ref,paybill'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%{$s}%")
                  ->orWhere('label', 'like', "%{$s}%")
                  ->orWhereHas('account', function ($q2) use ($s) {
                      $q2->where('business_name', 'like', "%{$s}%")
                         ->orWhere('paybill', 'like', "%{$s}%");
                  });
            });
        }

        $ips = $query->get();

        $pendingCount = IpWhitelist::where('status', 'pending')->count();
        $suspendedCount = IpWhitelist::where('status', 'suspended')->count();

        return response()->json(['ips' => $ips, 'pending_count' => $pendingCount, 'suspended_count' => $suspendedCount]);
    }

    /**
     * Admin: Approve an IP whitelist request.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $ip = IpWhitelist::find($id);
        if (!$ip) {
            return response()->json(['message' => 'IP whitelist entry not found.'], 404);
        }

        $ip->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes ?? null,
        ]);

        // Notify the account owner
        try {
            $owner = $ip->account->owner ?? null;
            if ($owner) {
                $owner->notify(new IpWhitelistApprovedNotification($ip->ip_address, $ip->label));
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to send IP approval email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'IP address approved.',
            'ip' => $ip->fresh()->load('account:id,business_name,account_ref,paybill'),
        ]);
    }

    /**
     * Admin: Reject an IP whitelist request.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $ip = IpWhitelist::find($id);
        if (!$ip) {
            return response()->json(['message' => 'IP whitelist entry not found.'], 404);
        }

        $ip->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes ?? $ip->admin_notes,
        ]);

        return response()->json([
            'message' => 'IP address rejected.',
            'ip' => $ip->fresh()->load('account:id,business_name,account_ref,paybill'),
        ]);
    }

    /**
     * Admin: Suspend (deactivate) an approved IP.
     */
    public function suspend(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $ip = IpWhitelist::find($id);
        if (!$ip) {
            return response()->json(['message' => 'IP whitelist entry not found.'], 404);
        }

        $ip->update([
            'status' => 'suspended',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes ?? $ip->admin_notes,
        ]);

        return response()->json([
            'message' => 'IP address suspended.',
            'ip' => $ip->fresh()->load('account:id,business_name,account_ref,paybill'),
        ]);
    }

    /**
     * Admin: Reactivate a suspended or rejected IP.
     */
    public function reactivate(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $ip = IpWhitelist::find($id);
        if (!$ip) {
            return response()->json(['message' => 'IP whitelist entry not found.'], 404);
        }

        $ip->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'admin_notes' => $request->admin_notes ?? $ip->admin_notes,
        ]);

        return response()->json([
            'message' => 'IP address reactivated.',
            'ip' => $ip->fresh()->load('account:id,business_name,account_ref,paybill'),
        ]);
    }
}
