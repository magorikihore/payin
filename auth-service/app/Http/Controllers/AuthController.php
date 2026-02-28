<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Account;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $account = Account::create([
            'account_ref' => 'ACC-' . strtoupper(Str::random(8)),
            'business_name' => $validated['business_name'],
            'email' => $validated['email'],
            'country' => $country,
            'currency' => $currency,
            'kyc_submitted_at' => null,
            'status' => 'pending',
        ]);

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

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Please check your username or password'], 401);
        }

        $user = Auth::user();

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

        $token = $user->createToken('authToken')->accessToken;
        $user->load('account');

        $userData = $user->toArray();
        $userData['admin_permissions'] = $user->getEffectiveAdminPermissions();

        return response()->json(['user' => $userData, 'token' => $token], 200);
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
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password changed successfully.']);
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
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'business_license' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
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

        // Mark as pending review when KYC is submitted/updated
        $data['kyc_submitted_at'] = now();
        if ($account->status !== 'active') {
            $data['status'] = 'pending';
        }

        $account->update($data);
        $account->refresh();

        return response()->json([
            'message' => 'KYC details updated successfully. Your account is under review.',
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
            'callback_url' => 'nullable|url|max:500',
        ]);

        $account = $user->account;
        if (!$account) {
            return response()->json(['message' => 'No account found.'], 404);
        }

        $account->update(['callback_url' => $request->callback_url]);

        return response()->json([
            'message' => 'Callback URL updated successfully.',
            'callback_url' => $account->callback_url,
        ]);
    }
}
