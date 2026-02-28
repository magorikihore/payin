<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\AccountOpeningNotification;
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

        $account = Account::with(['users:id,account_id,firstname,lastname,name,email,role,created_at', 'owner:id,account_id,firstname,lastname,name,email'])->find($id);

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
     * Admin update KYC details for an account (edit fields + upload documents).
     */
    public function updateAccountKyc(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_accounts')) return $denied;

        $account = Account::find($id);
        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $request->validate([
            'business_name'        => 'sometimes|string|max:191',
            'business_type'        => 'nullable|string|max:191',
            'registration_number'  => 'nullable|string|max:191',
            'tin_number'           => 'nullable|string|max:191',
            'email'                => 'nullable|email|max:191',
            'phone'                => 'nullable|string|max:20',
            'address'              => 'nullable|string|max:500',
            'city'                 => 'nullable|string|max:191',
            'country'              => 'nullable|string|max:191',
            'id_type'              => 'nullable|in:national_id,passport,drivers_license',
            'id_number'            => 'nullable|string|max:191',
            'bank_name'            => 'nullable|string|max:191',
            'bank_account_name'    => 'nullable|string|max:191',
            'bank_account_number'  => 'nullable|string|max:191',
            'bank_swift'           => 'nullable|string|max:191',
            'bank_branch'          => 'nullable|string|max:191',
            'id_document'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'business_license'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'certificate_of_incorporation' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_clearance'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $data = $request->only([
            'business_name', 'business_type', 'registration_number', 'tin_number',
            'email', 'phone', 'address', 'city', 'country',
            'id_type', 'id_number',
            'bank_name', 'bank_account_name', 'bank_account_number', 'bank_swift', 'bank_branch',
        ]);

        // Handle ID document upload
        if ($request->hasFile('id_document')) {
            $path = $request->file('id_document')->store('kyc/id-documents', 'public');
            $data['id_document_url'] = '/storage/' . $path;
        }

        // Handle business license upload
        if ($request->hasFile('business_license')) {
            $path = $request->file('business_license')->store('kyc/business-licenses', 'public');
            $data['business_license_url'] = '/storage/' . $path;
        }

        // Handle certificate of incorporation upload
        if ($request->hasFile('certificate_of_incorporation')) {
            $path = $request->file('certificate_of_incorporation')->store('kyc/certificates', 'public');
            $data['certificate_of_incorporation_url'] = '/storage/' . $path;
        }

        // Handle tax clearance upload
        if ($request->hasFile('tax_clearance')) {
            $path = $request->file('tax_clearance')->store('kyc/tax-clearances', 'public');
            $data['tax_clearance_url'] = '/storage/' . $path;
        }

        $account->update($data);
        $account->refresh();

        return response()->json([
            'message' => 'KYC details updated successfully.',
            'account' => $account,
        ]);
    }

    /**
     * Admin creates a new business account with full KYC and sends email to user.
     */
    public function createBusiness(Request $request): JsonResponse
    {
        if ($denied = $this->checkAdminAccess($request, 'admin_accounts')) return $denied;

        $request->validate([
            'firstname'            => 'required|string|max:191',
            'lastname'             => 'required|string|max:191',
            'email'                => 'required|email|max:191|unique:users,email',
            'business_name'        => 'required|string|max:191',
            'business_type'        => 'nullable|string|max:191',
            'registration_number'  => 'nullable|string|max:191',
            'tin_number'           => 'nullable|string|max:191',
            'phone'                => 'nullable|string|max:20',
            'address'              => 'nullable|string|max:500',
            'city'                 => 'nullable|string|max:191',
            'country'              => 'nullable|string|max:191',
            'id_type'              => 'nullable|in:national_id,passport,drivers_license',
            'id_number'            => 'nullable|string|max:191',
            'bank_name'            => 'nullable|string|max:191',
            'bank_account_name'    => 'nullable|string|max:191',
            'bank_account_number'  => 'nullable|string|max:191',
            'bank_swift'           => 'nullable|string|max:191',
            'bank_branch'          => 'nullable|string|max:191',
            'paybill'              => 'nullable|string|max:50',
            'status'               => 'nullable|in:pending,active',
            'id_document'                    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'certificate_of_incorporation'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'business_license'               => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_clearance'                  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Map country to currency
        $currencyMap = [
            'Tanzania' => 'TZS', 'Kenya' => 'KES', 'Uganda' => 'UGX', 'Rwanda' => 'RWF',
            'Burundi' => 'BIF', 'DRC' => 'CDF', 'Mozambique' => 'MZN', 'Malawi' => 'MWK',
            'Zambia' => 'ZMW', 'South Africa' => 'ZAR', 'Nigeria' => 'NGN', 'Ghana' => 'GHS',
        ];
        $country = $request->country ?? 'Tanzania';
        $currency = $currencyMap[$country] ?? 'TZS';

        // Create account
        $accountData = [
            'account_ref' => 'ACC-' . strtoupper(Str::random(8)),
            'business_name' => $request->business_name,
            'email' => $request->email,
            'country' => $country,
            'currency' => $currency,
            'status' => $request->status ?? 'pending',
            'business_type' => $request->business_type,
            'registration_number' => $request->registration_number,
            'tin_number' => $request->tin_number,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'id_type' => $request->id_type,
            'id_number' => $request->id_number,
            'bank_name' => $request->bank_name,
            'bank_account_name' => $request->bank_account_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_swift' => $request->bank_swift,
            'bank_branch' => $request->bank_branch,
            'paybill' => $request->paybill,
            'kyc_submitted_at' => now(),
        ];

        // Handle document uploads
        if ($request->hasFile('id_document')) {
            $path = $request->file('id_document')->store('kyc/id-documents', 'public');
            $accountData['id_document_url'] = '/storage/' . $path;
        }
        if ($request->hasFile('certificate_of_incorporation')) {
            $path = $request->file('certificate_of_incorporation')->store('kyc/certificates', 'public');
            $accountData['certificate_of_incorporation_url'] = '/storage/' . $path;
        }
        if ($request->hasFile('business_license')) {
            $path = $request->file('business_license')->store('kyc/business-licenses', 'public');
            $accountData['business_license_url'] = '/storage/' . $path;
        }
        if ($request->hasFile('tax_clearance')) {
            $path = $request->file('tax_clearance')->store('kyc/tax-clearances', 'public');
            $accountData['tax_clearance_url'] = '/storage/' . $path;
        }

        // If status is active, mark KYC as approved
        if (($request->status ?? 'pending') === 'active') {
            $accountData['kyc_approved_at'] = now();
            $accountData['kyc_approved_by'] = $request->user()->id;
        }

        $account = Account::create($accountData);

        // Generate random password
        $password = Str::random(10);

        // Create owner user
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'name' => $request->firstname . ' ' . $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($password),
            'account_id' => $account->id,
            'role' => 'owner',
        ]);

        // Send account opening email with credentials
        try {
            $user->load('account');
            $user->notify(new AccountOpeningNotification($password));
        } catch (\Throwable $e) {
            \Log::warning('Account opening email failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Business account created successfully. Login credentials sent to ' . $request->email,
            'account' => $account->fresh()->load('users:id,account_id,firstname,lastname,email,role'),
        ], 201);
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
                $q->where('firstname', 'like', "%{$s}%")
                  ->orWhere('lastname', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->select('id', 'account_id', 'firstname', 'lastname', 'name', 'email', 'role', 'created_at')
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
            ->select('id', 'firstname', 'lastname', 'name', 'email', 'role', 'permissions', 'created_at')
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
            'firstname' => 'required|string|max:191',
            'lastname' => 'required|string|max:191',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(User::ADMIN_PERMISSIONS)),
        ]);

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'name' => $request->firstname . ' ' . $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin_user',
            'account_id' => null,
            'permissions' => $request->permissions,
        ]);

        return response()->json([
            'message' => "Admin user {$user->name} created successfully.",
            'user' => $user->only('id', 'firstname', 'lastname', 'name', 'email', 'role', 'permissions', 'created_at'),
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
            'firstname' => 'sometimes|string|max:191',
            'lastname' => 'sometimes|string|max:191',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'permissions' => 'sometimes|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(User::ADMIN_PERMISSIONS)),
        ]);

        $data = [];
        if ($request->has('firstname')) $data['firstname'] = $request->firstname;
        if ($request->has('lastname')) $data['lastname'] = $request->lastname;
        if ($request->has('firstname') || $request->has('lastname')) {
            $data['name'] = ($request->firstname ?? $user->firstname) . ' ' . ($request->lastname ?? $user->lastname);
        }
        if ($request->has('email')) $data['email'] = $request->email;
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);
        if ($request->has('permissions')) $data['permissions'] = $request->permissions;

        $user->update($data);

        return response()->json([
            'message' => "Admin user {$user->name} updated successfully.",
            'user' => $user->fresh()->only('id', 'firstname', 'lastname', 'name', 'email', 'role', 'permissions', 'created_at'),
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

    /**
     * Get current mail configuration.
     * Only super_admin.
     */
    public function getMailConfig(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $envPath = base_path('.env');
        $env = file_exists($envPath) ? file_get_contents($envPath) : '';

        $keys = ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'];
        $config = [];
        foreach ($keys as $key) {
            if (preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $env, $m)) {
                $config[$key] = trim($m[1]);
            } else {
                $config[$key] = '';
            }
        }

        return response()->json(['config' => $config]);
    }

    /**
     * Update mail configuration in .env.
     * Only super_admin.
     */
    public function updateMailConfig(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $request->validate([
            'MAIL_MAILER' => 'required|string|in:smtp,sendmail,log',
            'MAIL_HOST' => 'nullable|string|max:255',
            'MAIL_PORT' => 'nullable|integer|min:1|max:65535',
            'MAIL_USERNAME' => 'nullable|string|max:500',
            'MAIL_PASSWORD' => 'nullable|string|max:500',
            'MAIL_ENCRYPTION' => 'nullable|string|in:tls,ssl,null',
            'MAIL_FROM_ADDRESS' => 'nullable|string|max:255',
            'MAIL_FROM_NAME' => 'nullable|string|max:255',
        ]);

        $envPath = base_path('.env');
        $env = file_exists($envPath) ? file_get_contents($envPath) : '';

        $keys = ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'];
        foreach ($keys as $key) {
            $value = $request->input($key, '');
            // Quote value if it contains spaces
            $envValue = (str_contains((string) $value, ' ')) ? '"' . $value . '"' : $value;

            if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $env)) {
                $env = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $key . '=' . $envValue, $env);
            } else {
                $env .= "\n" . $key . '=' . $envValue;
            }
        }

        file_put_contents($envPath, $env);

        // Clear config cache so changes take effect
        \Artisan::call('config:clear');

        return response()->json(['message' => 'Mail configuration updated successfully.']);
    }

    /**
     * Send a test email to verify mail configuration.
     * Only super_admin.
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from Payin to verify your mail configuration is working correctly.', function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Payin — Test Email');
            });

            return response()->json(['message' => 'Test email sent to ' . $request->email]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to send test email: ' . $e->getMessage()], 422);
        }
    }

    // ==================== EMAIL TEMPLATES ====================

    /**
     * List all email templates (seed defaults if none exist).
     */
    public function emailTemplates(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        // Auto-seed defaults if table is empty
        if (EmailTemplate::count() === 0) {
            foreach (EmailTemplate::defaults() as $tpl) {
                EmailTemplate::create($tpl);
            }
        }

        return response()->json(['templates' => EmailTemplate::orderBy('id')->get()]);
    }

    /**
     * Get a single email template.
     */
    public function emailTemplate(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $tpl = EmailTemplate::find($id);
        if (!$tpl) return response()->json(['message' => 'Template not found.'], 404);

        return response()->json(['template' => $tpl]);
    }

    /**
     * Update an email template.
     */
    public function updateEmailTemplate(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $tpl = EmailTemplate::find($id);
        if (!$tpl) return response()->json(['message' => 'Template not found.'], 404);

        $request->validate([
            'subject' => 'required|string|max:255',
            'greeting' => 'required|string|max:500',
            'body' => 'required|string|max:5000',
            'action_text' => 'nullable|string|max:100',
            'action_url' => 'nullable|string|max:500',
            'footer' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $tpl->update($request->only(['subject', 'greeting', 'body', 'action_text', 'action_url', 'footer', 'is_active']));

        return response()->json(['message' => 'Template updated.', 'template' => $tpl->fresh()]);
    }

    /**
     * Reset a template to its default values.
     */
    public function resetEmailTemplate(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $tpl = EmailTemplate::find($id);
        if (!$tpl) return response()->json(['message' => 'Template not found.'], 404);

        $defaults = collect(EmailTemplate::defaults())->firstWhere('key', $tpl->key);
        if ($defaults) {
            $tpl->update($defaults);
        }

        return response()->json(['message' => 'Template reset to default.', 'template' => $tpl->fresh()]);
    }

    /**
     * Create a new custom email template.
     */
    public function createEmailTemplate(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $request->validate([
            'key' => 'required|string|max:50|unique:email_templates,key|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'greeting' => 'required|string|max:500',
            'body' => 'required|string|max:5000',
            'action_text' => 'nullable|string|max:100',
            'action_url' => 'nullable|string|max:500',
            'footer' => 'nullable|string|max:500',
        ]);

        $tpl = EmailTemplate::create([
            'key' => $request->key,
            'name' => $request->name,
            'subject' => $request->subject,
            'greeting' => $request->greeting,
            'body' => $request->body,
            'action_text' => $request->action_text,
            'action_url' => $request->action_url,
            'footer' => $request->footer ?? '— Payin Team',
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Template created.', 'template' => $tpl], 201);
    }

    /**
     * Delete a custom email template (cannot delete system templates).
     */
    public function deleteEmailTemplate(Request $request, $id): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $tpl = EmailTemplate::find($id);
        if (!$tpl) return response()->json(['message' => 'Template not found.'], 404);

        $systemKeys = ['welcome', 'password_reset', 'kyc_approved', 'kyc_rejected'];
        if (in_array($tpl->key, $systemKeys)) {
            return response()->json(['message' => 'Cannot delete system templates. You can disable them instead.'], 422);
        }

        $tpl->delete();
        return response()->json(['message' => 'Template deleted.']);
    }

    /**
     * Send a custom notification email using a template.
     * Can send to: specific emails, all users, or all account owners.
     */
    public function sendTemplateEmail(Request $request): JsonResponse
    {
        if ($denied = $this->checkSuperAdmin($request)) return $denied;

        $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'send_to' => 'required|in:emails,all_users,all_owners',
            'emails' => 'required_if:send_to,emails|array',
            'emails.*' => 'email',
        ]);

        $tpl = EmailTemplate::find($request->template_id);
        if (!$tpl) return response()->json(['message' => 'Template not found.'], 404);

        $recipients = collect();

        if ($request->send_to === 'emails') {
            $recipients = User::whereIn('email', $request->emails)->get();
            // Also include emails not in users table
            $foundEmails = $recipients->pluck('email')->toArray();
            $extraEmails = array_diff($request->emails, $foundEmails);
        } elseif ($request->send_to === 'all_users') {
            $recipients = User::whereNotNull('account_id')->get();
        } elseif ($request->send_to === 'all_owners') {
            $recipients = User::where('role', 'owner')->get();
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $user) {
            try {
                $user->notify(new \App\Notifications\CustomTemplateNotification($tpl));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                \Log::warning("Failed to send template email to {$user->email}: " . $e->getMessage());
            }
        }

        // Send to extra emails (not in users table)
        if (!empty($extraEmails)) {
            foreach ($extraEmails as $email) {
                try {
                    \Illuminate\Support\Facades\Mail::raw(
                        $tpl->body,
                        function ($message) use ($email, $tpl) {
                            $message->to($email)->subject($tpl->subject);
                        }
                    );
                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
        }

        return response()->json([
            'message' => "Sent to {$sent} recipient(s)." . ($failed ? " {$failed} failed." : ''),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }
}
