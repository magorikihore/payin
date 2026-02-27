<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use App\Notifications\KycApprovedNotification;
use App\Notifications\KycRejectedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Check admin-level access (super_admin or admin_user with specific permission).
     */
    private function checkAdminAccess(Request $request, string $permission = null): ?JsonResponse
    {
        $user = $request->user();
        if (!$user->isAdminLevel()) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }
        if ($permission && !$user->hasAdminPermission($permission)) {
            return response()->json(['message' => 'You do not have permission for this action.'], 403);
        }
        return null;
    }

    /**
     * Check super_admin only access.
     */
    private function checkSuperAdmin(Request $request): ?JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super admin access required.'], 403);
        }
        return null;
    }

    /**
     * Dashboard stats.
     */
    public function stats(Request $request): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_overview')) return $denied;

        return response()->json([
            'total_accounts' => Account::count(),
            'active_accounts' => Account::where('status', 'active')->count(),
            'pending_accounts' => Account::where('status', 'pending')->count(),
            'suspended_accounts' => Account::where('status', 'suspended')->count(),
            'total_users' => User::whereNotNull('account_id')->count(),
        ]);
    }

    /**
     * List all accounts with pagination & search.
     */
    public function accounts(Request $request): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_accounts')) return $denied;

        $query = Account::withCount('users');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('business_name', 'like', "%{$s}%")
                  ->orWhere('account_ref', 'like', "%{$s}%")
                  ->orWhere('paybill', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $accounts = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($accounts);
    }

    /**
     * Get single account details with users and KYC info.
     */
    public function accountDetail(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_accounts')) return $denied;

        $account = Account::with(['users:id,account_id,name,email,role,created_at', 'owner:id,account_id,name,email'])->find($id);

        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        // Build KYC completeness
        $kycFields = ['business_name', 'business_type', 'registration_number', 'tin_number', 'phone', 'address', 'city', 'id_type', 'id_number'];
        $filled = collect($kycFields)->filter(fn($f) => !empty($account->$f))->count();
        $account->kyc_completeness = round(($filled / count($kycFields)) * 100);

        return response()->json(['account' => $account]);
    }

    /**
     * Update account status (activate/suspend/close).
     */
    public function updateAccountStatus(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_accounts')) return $denied;

        $request->validate([
            'status' => 'required|in:pending,active,suspended,closed',
            'paybill' => 'nullable|string|max:50',
        ]);

        $account = Account::find($id);

        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $oldStatus = $account->status;
        $updateData = ['status' => $request->status];

        // Admin can set paybill
        if ($request->has('paybill')) {
            $updateData['paybill'] = $request->paybill;
        }

        $account->update($updateData);

        // Track KYC approval
        if ($request->status === 'active' && $oldStatus === 'pending') {
            $account->update([
                'kyc_approved_at' => now(),
                'kyc_approved_by' => $request->user()->id,
            ]);

            // Notify account owner
            try {
                $owner = $account->owner;
                if ($owner) {
                    $owner->notify(new KycApprovedNotification());
                }
            } catch (\Throwable $e) {
                \Log::warning('KYC approval email failed: ' . $e->getMessage());
            }
        }

        // Notify on suspension/rejection
        if ($request->status === 'suspended' && $oldStatus !== 'suspended') {
            try {
                $owner = $account->owner;
                if ($owner) {
                    $owner->notify(new KycRejectedNotification($account->kyc_notes ?? ''));
                }
            } catch (\Throwable $e) {
                \Log::warning('KYC rejection email failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => "Account status updated to {$request->status}.",
            'account' => $account->fresh(),
        ]);
    }

    /**
     * Update KYC notes for an account.
     */
    public function updateKycNotes(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_accounts')) return $denied;

        $request->validate(['kyc_notes' => 'required|string|max:1000']);

        $account = Account::find($id);
        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $account->update(['kyc_notes' => $request->kyc_notes]);

        return response()->json(['message' => 'KYC notes updated.', 'account' => $account]);
    }

    /**
     * List all users across all accounts.
     */
    public function users(Request $request): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_users')) return $denied;

        $query = User::with('account:id,business_name,account_ref,status')->whereNotNull('account_id');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->select('id', 'account_id', 'name', 'email', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($users);
    }

    /**
     * Update account rate limit (requests per minute).
     */
    public function updateRateLimit(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_accounts')) return $denied;

        $request->validate([
            'rate_limit' => 'required|integer|min:1|max:10000',
        ]);

        $account = Account::find($id);
        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $account->update(['rate_limit' => $request->rate_limit]);

        return response()->json([
            'message' => "Rate limit updated to {$request->rate_limit} requests/min.",
            'account' => $account->fresh(),
        ]);
    }

    /**
     * Reset a user's password (admin).
     */
    public function resetPassword(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_users')) return $denied;

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Generate a random 12-char password
        $newPassword = Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        return response()->json([
            'message' => "Password reset successfully for {$user->name}.",
            'new_password' => $newPassword,
        ]);
    }

    // ==================== ADMIN USER MANAGEMENT ====================

    /**
     * List all admin-level users (super_admin + admin_user).
     * Only super_admin can manage admin users.
     */
    public function adminUsers(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $users = User::whereIn('role', ['super_admin', 'admin_user'])
            ->select('id', 'name', 'email', 'role', 'permissions', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'admin_users' => $users,
            'available_permissions' => User::ADMIN_PERMISSIONS,
        ]);
    }

    /**
     * Create a new admin user with specific permissions.
     * Only super_admin can create admin users.
     */
    public function createAdminUser(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(User::ADMIN_PERMISSIONS)),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin_user',
            'account_id' => null,
            'permissions' => $request->permissions,
        ]);

        return response()->json([
            'message' => "Admin user {$user->name} created successfully.",
            'user' => $user->only('id', 'name', 'email', 'role', 'permissions', 'created_at'),
        ], 201);
    }

    /**
     * Update an admin user's permissions.
     * Only super_admin can update admin user permissions.
     */
    public function updateAdminUser(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $user = User::find($id);
        if (!$user || $user->role !== 'admin_user') {
            return response()->json(['message' => 'Admin user not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:191',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'permissions' => 'sometimes|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(User::ADMIN_PERMISSIONS)),
        ]);

        $data = [];
        if ($request->has('name')) $data['name'] = $request->name;
        if ($request->has('email')) $data['email'] = $request->email;
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);
        if ($request->has('permissions')) $data['permissions'] = $request->permissions;

        $user->update($data);

        return response()->json([
            'message' => "Admin user {$user->name} updated successfully.",
            'user' => $user->fresh()->only('id', 'name', 'email', 'role', 'permissions', 'created_at'),
        ]);
    }

    /**
     * Delete an admin user.
     * Only super_admin can delete admin users. Cannot delete self or other super_admins.
     */
    public function deleteAdminUser(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $user = User::find($id);
        if (!$user || $user->role !== 'admin_user') {
            return response()->json(['message' => 'Admin user not found.'], 404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => "Admin user {$user->name} deleted successfully."]);
    }
}
