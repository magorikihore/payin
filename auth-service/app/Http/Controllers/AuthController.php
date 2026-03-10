<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Account;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Notifications\AdminKycSubmittedNotification;
use App\Notifications\AdminNewRegistrationNotification;
use App\Notifications\TwoFactorCodeNotification;
use App\Notifications\AccountLockedNotification;
use App\Notifications\NewIpLoginNotification;
use App\Notifications\FailedTwoFactorNotification;
use App\Models\AdminSetting;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Map country to default currency if not provided
        $currencyMap = [
            'Tanzania' => 'TZS', 'Kenya' => 'KES', 'Uganda' => 'UGX', 'Rwanda' => 'RWF',
            'Burundi' => 'BIF', 'DRC' => 'CDF', 'Mozambique' => 'MZN', 'Malawi' => 'MWK',
            'Zambia' => 'ZMW', 'South Africa' => 'ZAR', 'Nigeria' => 'NGN', 'Ghana' => 'GHS',
        ];
        $country = $validated['country'] ?? 'Tanzania';
        $currency = $validated['currency'] ?? ($currencyMap[$country] ?? 'TZS');

        // Create account (KYC not yet submitted — user must complete after login)
        $accountData = [
            'account_ref' => 'ACC-' . strtoupper(Str::random(8)),
            'business_name' => $validated['business_name'],
            'email' => $validated['email'],
            'country' => $country,
            'currency' => $currency,
            'kyc_submitted_at' => null,
            'status' => 'pending',
        ];

        // Handle referral code — link new account to referrer
        if (!empty($validated['referral_code'])) {
            $referrer = Account::where('referral_code', $validated['referral_code'])->first();
            if ($referrer) {
                $accountData['referred_by'] = $referrer->id;
                $accountData['referred_at'] = now();
            }
        }

        $account = Account::create($accountData);

        // Create owner user
        $user = User::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'name' => $validated['firstname'] . ' ' . $validated['lastname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'account_id' => $account->id,
            'role' => 'owner',
        ]);

        $token = $user->createToken('authToken')->accessToken;
        $user->load('account');

        // Send welcome notification
        try {
            $user->notify(new WelcomeNotification());
        } catch (\Throwable $e) {
            // Don't fail registration if email fails
            \Log::warning('Welcome email failed: ' . $e->getMessage());
        }

        // Notify admins about new registration
        try {
            $notification = new AdminNewRegistrationNotification([
                'business_name' => $validated['business_name'],
                'owner_name' => $validated['firstname'] . ' ' . $validated['lastname'],
                'email' => $validated['email'],
                'country' => $country,
                'account_ref' => $account->account_ref,
            ]);

            // Send to all admin users
            $admins = User::whereIn('role', ['super_admin', 'admin_user'])->get();
            foreach ($admins as $admin) {
                try {
                    $admin->notify($notification);
                } catch (\Throwable $e) {
                    \Log::warning('Admin registration notification failed for ' . $admin->email . ': ' . $e->getMessage());
                }
            }

            // Send to configured notification emails
            $notifEmails = AdminSetting::getNotificationEmails();
            $adminEmails = $admins->pluck('email')->map(fn($e) => strtolower($e))->toArray();
            foreach ($notifEmails as $email) {
                if (in_array(strtolower($email), $adminEmails)) continue;
                try {
                    Notification::route('mail', $email)->notify($notification);
                } catch (\Throwable $e) {
                    \Log::warning('Registration notification to ' . $email . ' failed: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Admin new-registration notification failed: ' . $e->getMessage());
        }

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // Check if account is locked before attempting login
        $user = User::where('email', $credentials['email'])->first();
        if ($user && $user->isLockedOut()) {
            $minutes = $user->lockoutMinutesRemaining();
            return response()->json([
                'message' => "Account locked due to too many failed attempts. Try again in {$minutes} minute(s).",
                'locked_until' => $user->locked_until->toIso8601String(),
            ], 423);
        }

        if (!Auth::attempt($credentials)) {
            // Record failed attempt if user exists
            if ($user) {
                $user->recordFailedLogin();
                $remaining = User::MAX_LOGIN_ATTEMPTS - ($user->failed_login_attempts + 1);
                if ($user->isLockedOut()) {
                    // Alert: account locked
                    $this->sendAccountLockedAlerts($user, $request->ip());
                    return response()->json([
                        'message' => 'Account locked due to too many failed attempts. Try again in ' . User::LOCKOUT_MINUTES . ' minute(s).',
                        'locked_until' => $user->locked_until->toIso8601String(),
                    ], 423);
                }
            }
            return response()->json(['message' => 'Please check your username or password'], 401);
        }

        $user = Auth::user();

        // Reset failed attempts on successful password check
        $user->resetFailedLogins();

        // Log successful password authentication
        ActivityLog::record('login', 'User authenticated successfully', $user->id, $user->account_id, $request->ip());

        // Check if user is banned
        if ($user->is_banned) {
            Auth::logout();
            return response()->json(['message' => 'Your account has been suspended. Contact support for more information.'], 403);
        }

        // If 2FA is enabled, generate code and require verification
        if ($user->two_factor_enabled) {
            $code = $user->generateTwoFactorCode();

            try {
                $user->notify(new TwoFactorCodeNotification($code));
            } catch (\Throwable $e) {
                \Log::warning('2FA email failed for ' . $user->email . ': ' . $e->getMessage());
            }

            Auth::logout();

            return response()->json([
                'two_factor_required' => true,
                'email' => $user->email,
                'message' => 'A verification code has been sent to your email.',
            ], 200);
        }

        // Check account status (skip for super_admin)
        if (!$user->isSuperAdmin() && $user->account) {
            // If account is already active, skip KYC checks
            if ($user->account->status === 'active') {
                // Account approved — proceed to normal login
            } elseif (is_null($user->account->kyc_submitted_at)) {
                // KYC not yet submitted — force user to complete KYC first
                $token = $user->createToken('authToken')->accessToken;
                $user->load('account');
                return response()->json([
                    'user' => $user,
                    'token' => $token,
                    'kyc_required' => true,
                    'message' => 'Please complete your KYC information to activate your account.'
                ], 200);
            } elseif ($user->account->status === 'pending') {
                // KYC submitted but still pending approval
                $token = $user->createToken('authToken')->accessToken;
                $user->load('account');
                return response()->json([
                    'user' => $user,
                    'token' => $token,
                    'pending' => true,
                    'message' => 'Your account is pending KYC approval. Please wait for admin verification.'
                ], 200);
            } else {
                // suspended / closed
                Auth::logout();
                return response()->json(['message' => 'Your account has been suspended. Contact support.'], 403);
            }
        }

        // Record login IP and check for new IP
        $previousIp = $user->last_login_ip;
        $user->recordLoginIp($request->ip());
        if ($previousIp && $previousIp !== $request->ip()) {
            try {
                $user->notify(new NewIpLoginNotification($request->ip(), $previousIp));
            } catch (\Throwable $e) {
                \Log::warning('New IP alert failed for ' . $user->email . ': ' . $e->getMessage());
            }
        }

        $token = $user->createToken('authToken')->accessToken;
        $user->load('account');

        $userData = $user->toArray();
        $userData['admin_permissions'] = $user->getEffectiveAdminPermissions();
        $userData['must_change_password'] = (bool) $user->must_change_password;

        return response()->json(['user' => $userData, 'token' => $token], 200);
    }

    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->two_factor_code) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        // Check if account is locked
        if ($user->isLockedOut()) {
            $minutes = $user->lockoutMinutesRemaining();
            return response()->json([
                'message' => "Account locked due to too many failed attempts. Try again in {$minutes} minute(s).",
                'locked_until' => $user->locked_until->toIso8601String(),
            ], 423);
        }

        // Check if code is expired
        if ($user->two_factor_expires_at && $user->two_factor_expires_at->isPast()) {
            $user->clearTwoFactorCode();
            return response()->json(['message' => 'Verification code has expired. Please login again.'], 422);
        }

        // Verify the code
        if (!Hash::check($request->code, $user->two_factor_code)) {
            $user->recordFailedLogin();
            $attempts = $user->failed_login_attempts;

            // Alert user on 3+ failed 2FA attempts
            if ($attempts >= 3) {
                try {
                    $user->notify(new FailedTwoFactorNotification($request->ip(), $attempts));
                } catch (\Throwable $e) {
                    \Log::warning('Failed 2FA alert failed for ' . $user->email . ': ' . $e->getMessage());
                }
            }

            if ($user->isLockedOut()) {
                $user->clearTwoFactorCode();
                $this->sendAccountLockedAlerts($user, $request->ip());
                return response()->json([
                    'message' => 'Account locked due to too many failed attempts. Try again in ' . User::LOCKOUT_MINUTES . ' minute(s).',
                    'locked_until' => $user->locked_until->toIso8601String(),
                ], 423);
            }
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        // Clear the 2FA code
        $user->clearTwoFactorCode();
        $user->resetFailedLogins();

        // Log successful 2FA verification
        ActivityLog::record('login_2fa', 'User verified 2FA and logged in', $user->id, $user->account_id, $request->ip());

        // Check if user is banned (re-check in case status changed)
        if ($user->is_banned) {
            return response()->json(['message' => 'Your account has been suspended. Contact support for more information.'], 403);
        }

        // Check account status (skip for super_admin)
        if (!$user->isSuperAdmin() && $user->account) {
            if ($user->account->status === 'active') {
                // proceed
            } elseif (is_null($user->account->kyc_submitted_at)) {
                $token = $user->createToken('authToken')->accessToken;
                $user->load('account');
                return response()->json([
                    'user' => $user,
                    'token' => $token,
                    'kyc_required' => true,
                    'message' => 'Please complete your KYC information to activate your account.'
                ], 200);
            } elseif ($user->account->status === 'pending') {
                $token = $user->createToken('authToken')->accessToken;
                $user->load('account');
                return response()->json([
                    'user' => $user,
                    'token' => $token,
                    'pending' => true,
                    'message' => 'Your account is pending KYC approval. Please wait for admin verification.'
                ], 200);
            } else {
                return response()->json(['message' => 'Your account has been suspended. Contact support.'], 403);
            }
        }

        // Record login IP and check for new IP
        $previousIp = $user->last_login_ip;
        $user->recordLoginIp($request->ip());
        if ($previousIp && $previousIp !== $request->ip()) {
            try {
                $user->notify(new NewIpLoginNotification($request->ip(), $previousIp));
            } catch (\Throwable $e) {
                \Log::warning('New IP alert failed for ' . $user->email . ': ' . $e->getMessage());
            }
        }

        $token = $user->createToken('authToken')->accessToken;
        $user->load('account');

        $userData = $user->toArray();
        $userData['admin_permissions'] = $user->getEffectiveAdminPermissions();
        $userData['must_change_password'] = (bool) $user->must_change_password;

        return response()->json(['user' => $userData, 'token' => $token], 200);
    }

    public function resendTwoFactorCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)
            ->where('two_factor_enabled', true)
            ->first();

        if (!$user) {
            // Don't reveal whether the user exists
            return response()->json(['message' => 'If 2FA is enabled, a new code has been sent.']);
        }

        $code = $user->generateTwoFactorCode();

        try {
            $user->notify(new TwoFactorCodeNotification($code));
        } catch (\Throwable $e) {
            \Log::warning('2FA resend email failed for ' . $user->email . ': ' . $e->getMessage());
        }

        return response()->json(['message' => 'If 2FA is enabled, a new code has been sent.']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('account');
        $userData = $user->toArray();
        $userData['effective_permissions'] = $user->getEffectivePermissions();
        $userData['admin_permissions'] = $user->getEffectiveAdminPermissions();
        return response()->json($userData);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Revoke all existing tokens to invalidate old sessions
        $user->tokens()->delete();

        // Clear force password change flag
        if ($user->must_change_password) {
            $user->update(['must_change_password' => false]);
        }

        // Log password change
        ActivityLog::log($request, 'password_change', 'User changed password');

        // Issue a fresh token for the current session
        $token = $user->createToken('authToken')->accessToken;

        return response()->json([
            'message' => 'Password changed successfully. All other sessions have been logged out.',
            'token' => $token,
        ]);
    }

    public function toggleTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Require password confirmation to change 2FA setting
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password is incorrect.'], 422);
        }

        $user->update(['two_factor_enabled' => $request->enabled]);
        $user->clearTwoFactorCode();

        $status = $request->enabled ? 'enabled' : 'disabled';

        // Log 2FA toggle
        ActivityLog::log($request, '2fa_toggle', "User {$status} two-factor authentication");

        return response()->json([
            'message' => "Two-factor authentication has been {$status}.",
            'two_factor_enabled' => (bool) $request->enabled,
        ]);
    }

    public function getTwoFactorStatus(Request $request): JsonResponse
    {
        return response()->json([
            'two_factor_enabled' => (bool) $request->user()->two_factor_enabled,
        ]);
    }

    /**
     * Get callback URL configuration.
     */
    public function getCallback(Request $request): JsonResponse
    {
        $user = $request->user();
        $account = $user->account;

        if (!$account) {
            return response()->json(['message' => 'No account found.'], 404);
        }

        return response()->json([
            'callback_url' => $account->callback_url,
        ]);
    }

    /**
     * Get KYC data for the current account.
     */
    public function getKyc(Request $request): JsonResponse
    {
        $user = $request->user();
        $account = $user->account;

        if (!$account) {
            return response()->json(['message' => 'No account found.'], 404);
        }

        return response()->json([
            'kyc' => [
                'business_name' => $account->business_name,
                'business_type' => $account->business_type,
                'registration_number' => $account->registration_number,
                'tin_number' => $account->tin_number,
                'address' => $account->address,
                'city' => $account->city,
                'country' => $account->country,
                'bank_name' => $account->bank_name,
                'bank_account_name' => $account->bank_account_name,
                'bank_account_number' => $account->bank_account_number,
                'bank_swift' => $account->bank_swift,
                'bank_branch' => $account->bank_branch,
                'crypto_wallet_address' => $account->crypto_wallet_address,
                'crypto_network' => $account->crypto_network,
                'crypto_currency' => $account->crypto_currency,
                'id_type' => $account->id_type,
                'id_number' => $account->id_number,
                'id_document_url' => $account->id_document_url,
                'business_license_url' => $account->business_license_url,
                'certificate_of_incorporation_url' => $account->certificate_of_incorporation_url,
                'tax_clearance_url' => $account->tax_clearance_url,
                'tin_certificate_url' => $account->tin_certificate_url,
                'company_memorandum_url' => $account->company_memorandum_url,
                'company_resolution_url' => $account->company_resolution_url,
                'kyc_update_allowed' => (bool) $account->kyc_update_allowed,
                'status' => $account->status,
                'kyc_submitted_at' => $account->kyc_submitted_at,
                'kyc_approved_at' => $account->kyc_approved_at,
                'kyc_notes' => $account->kyc_notes,
            ],
        ]);
    }

    /**
     * Update KYC details and submit for review.
     */
    public function updateKyc(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only account owner or admin can update KYC.'], 403);
        }

        $account = $user->account;
        if (!$account) {
            return response()->json(['message' => 'No account found.'], 404);
        }

        // Require documents on first submission, nullable on updates
        $isFirstSubmission = is_null($account->kyc_submitted_at);
        $docRule = fn($field) => ($isFirstSubmission && !$account->$field ? 'required' : 'nullable') . '|file|mimes:jpg,jpeg,png,pdf|max:5120';

        $request->validate([
            'business_name' => 'required|string|max:191',
            'business_type' => 'nullable|string|max:191',
            'registration_number' => 'nullable|string|max:191',
            'tin_number' => 'nullable|string|max:191',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'bank_name' => 'nullable|string|max:191',
            'bank_account_name' => 'nullable|string|max:191',
            'bank_account_number' => 'nullable|string|max:191',
            'bank_swift' => 'nullable|string|max:191',
            'bank_branch' => 'nullable|string|max:191',
            'crypto_wallet_address' => 'nullable|string|max:500',
            'crypto_network' => 'nullable|string|max:191',
            'crypto_currency' => 'nullable|string|max:191',
            'id_type' => 'required|in:national_id,passport,drivers_license',
            'id_number' => 'required|string|max:191',
            'id_document' => $docRule('id_document_url'),
            'certificate_of_incorporation' => $docRule('certificate_of_incorporation_url'),
            'business_license' => $docRule('business_license_url'),
            'tin_certificate' => $docRule('tin_certificate_url'),
            'tax_clearance' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'company_memorandum' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'company_resolution' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $data = $request->only([
            'business_name', 'business_type', 'registration_number', 'tin_number',
            'phone', 'address', 'city', 'country',
            'bank_name', 'bank_account_name', 'bank_account_number', 'bank_swift', 'bank_branch',
            'crypto_wallet_address', 'crypto_network', 'crypto_currency',
            'id_type', 'id_number',
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

        // Handle TIN certificate upload
        if ($request->hasFile('tin_certificate')) {
            $path = $request->file('tin_certificate')->store('kyc/tin-certificates', 'public');
            $data['tin_certificate_url'] = '/storage/' . $path;
        }

        // Handle company memorandum upload
        if ($request->hasFile('company_memorandum')) {
            $path = $request->file('company_memorandum')->store('kyc/memorandums', 'public');
            $data['company_memorandum_url'] = '/storage/' . $path;
        }

        // Handle company resolution upload
        if ($request->hasFile('company_resolution')) {
            $path = $request->file('company_resolution')->store('kyc/resolutions', 'public');
            $data['company_resolution_url'] = '/storage/' . $path;
        }

        // Mark as pending review when KYC is submitted/updated
        $data['kyc_submitted_at'] = now();
        if ($account->status !== 'active') {
            $data['status'] = 'pending';
        }

        // If account is active and admin granted update permission, revoke it after save
        if ($account->status === 'active' && $account->kyc_update_allowed) {
            $data['kyc_update_allowed'] = false;
        }

        $account->update($data);
        $account->refresh();

        // Notify admin users about new KYC submission
        try {
            $admins = User::whereIn('role', ['super_admin', 'admin_user'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new AdminKycSubmittedNotification([
                    'business_name' => $account->business_name ?? 'N/A',
                    'account_ref' => $account->account_ref ?? 'N/A',
                    'submitted_by' => $user->firstname . ' ' . $user->lastname,
                ]));
            }
        } catch (\Throwable $e) {
            \Log::warning('Admin KYC notification failed: ' . $e->getMessage());
        }

        $message = $account->status === 'active'
            ? 'Business details updated successfully.'
            : 'KYC details updated successfully. Your account is under review.';

        return response()->json([
            'message' => $message,
            'kyc' => [
                'business_name' => $account->business_name,
                'business_type' => $account->business_type,
                'registration_number' => $account->registration_number,
                'tin_number' => $account->tin_number,
                'address' => $account->address,
                'city' => $account->city,
                'country' => $account->country,
                'bank_name' => $account->bank_name,
                'bank_account_name' => $account->bank_account_name,
                'bank_account_number' => $account->bank_account_number,
                'bank_swift' => $account->bank_swift,
                'bank_branch' => $account->bank_branch,
                'crypto_wallet_address' => $account->crypto_wallet_address,
                'crypto_network' => $account->crypto_network,
                'crypto_currency' => $account->crypto_currency,
                'id_type' => $account->id_type,
                'id_number' => $account->id_number,
                'id_document_url' => $account->id_document_url,
                'business_license_url' => $account->business_license_url,
                'certificate_of_incorporation_url' => $account->certificate_of_incorporation_url,
                'tax_clearance_url' => $account->tax_clearance_url,
                'tin_certificate_url' => $account->tin_certificate_url,
                'company_memorandum_url' => $account->company_memorandum_url,
                'company_resolution_url' => $account->company_resolution_url,
                'kyc_update_allowed' => (bool) $account->kyc_update_allowed,
                'status' => $account->status,
                'kyc_submitted_at' => $account->kyc_submitted_at,
                'kyc_approved_at' => $account->kyc_approved_at,
            ],
        ]);
    }

    /**
     * Update callback URL configuration.
     */
    public function updateCallback(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only account owner or admin can update callback settings.'], 403);
        }

        $request->validate([
            'callback_url' => ['nullable', 'url', 'max:500', function ($attribute, $value, $fail) {
                if (!$value) return;

                // Enforce HTTPS
                if (!str_starts_with($value, 'https://')) {
                    $fail('Callback URL must use HTTPS.');
                    return;
                }

                // Block private/reserved IPs (SSRF protection)
                $host = parse_url($value, PHP_URL_HOST);
                if (!$host) {
                    $fail('Invalid callback URL.');
                    return;
                }

                $ip = gethostbyname($host);
                if ($ip !== $host) {
                    $long = ip2long($ip);
                    $blocked = [
                        [ip2long('10.0.0.0'), ip2long('10.255.255.255')],
                        [ip2long('172.16.0.0'), ip2long('172.31.255.255')],
                        [ip2long('192.168.0.0'), ip2long('192.168.255.255')],
                        [ip2long('127.0.0.0'), ip2long('127.255.255.255')],
                        [ip2long('169.254.0.0'), ip2long('169.254.255.255')],
                    ];
                    foreach ($blocked as [$start, $end]) {
                        if ($long >= $start && $long <= $end) {
                            $fail('Callback URL must not point to a private or internal address.');
                            return;
                        }
                    }
                }
            }],
        ]);

        $account = $user->account;
        if (!$account) {
            return response()->json(['message' => 'No account found.'], 404);
        }

        $account->update(['callback_url' => $request->callback_url]);\n\n        // Log callback URL change\n        ActivityLog::log($request, 'callback_update', 'Callback URL updated', ['url' => $request->callback_url]);

        return response()->json([
            'message' => 'Callback URL updated successfully.',
            'callback_url' => $account->callback_url,
        ]);
    }

    /**
     * Send account locked alerts to the user and admins.
     */
    private function sendAccountLockedAlerts(User $user, string $ip): void
    {
        // Alert the locked user
        try {
            $user->notify(new AccountLockedNotification($ip));
        } catch (\Throwable $e) {
            \Log::warning('Account locked alert failed for ' . $user->email . ': ' . $e->getMessage());
        }

        // Alert admins
        try {
            $admins = User::whereIn('role', ['super_admin', 'admin_user'])->get();
            foreach ($admins as $admin) {
                try {
                    Notification::route('mail', $admin->email)
                        ->notify(new AccountLockedNotification($ip, true));
                } catch (\Throwable $e) {
                    \Log::warning('Admin lockout alert failed for ' . $admin->email . ': ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Admin lockout alerts failed: ' . $e->getMessage());
        }
    }
}
