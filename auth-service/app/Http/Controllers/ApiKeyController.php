<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\IpWhitelist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * List API keys for the current account.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->account_id) {
            return response()->json(['message' => 'No account found.'], 404);
        }

        $keys = ApiKey::where('account_id', $user->account_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'label' => $key->label,
                    'api_key' => $key->api_key,
                    'status' => $key->status,
                    'last_used_at' => $key->last_used_at,
                    'expires_at' => $key->expires_at,
                    'created_at' => $key->created_at,
                ];
            });

        return response()->json(['api_keys' => $keys]);
    }

    /**
     * Generate a new API key pair.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'super_admin'])) {
            return response()->json(['message' => 'Only account owner can generate API keys.'], 403);
        }

        if (!$user->account_id) {
            return response()->json(['message' => 'No account found.'], 404);
        }

        // Check account is active
        $account = $user->account;
        if ($account && $account->status !== 'active') {
            return response()->json(['message' => 'Account must be active to generate API keys.'], 403);
        }

        $request->validate([
            'label' => 'nullable|string|max:100',
        ]);

        // Limit max API keys per account
        $existingCount = ApiKey::where('account_id', $user->account_id)
            ->where('status', 'active')
            ->count();

        if ($existingCount >= 5) {
            return response()->json(['message' => 'Maximum 5 active API keys allowed per account.'], 422);
        }

        $apiKey = 'pk_' . Str::random(32);
        $apiSecret = 'sk_' . Str::random(48);

        $key = ApiKey::create([
            'account_id' => $user->account_id,
            'label' => $request->label ?? 'API Key',
            'api_key' => $apiKey,
            'api_secret' => Hash::make($apiSecret),
        ]);

        // Return the secret only once (it's hashed in the DB)
        return response()->json([
            'message' => 'API key generated successfully. Save the secret — it will not be shown again.',
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'label' => $key->label,
            'id' => $key->id,
        ], 201);
    }

    /**
     * Revoke an API key.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'super_admin'])) {
            return response()->json(['message' => 'Only account owner can revoke API keys.'], 403);
        }

        $key = ApiKey::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (!$key) {
            return response()->json(['message' => 'API key not found.'], 404);
        }

        $key->update(['status' => 'revoked']);

        return response()->json(['message' => 'API key revoked successfully.']);
    }

    /**
     * Validate an API key (called by other services).
     * This is an internal endpoint used by the API key middleware.
     */
    public function validate(Request $request): JsonResponse
    {
        if (!$request->api_key || !$request->api_secret) {
            return response()->json(['message' => 'api_key and api_secret are required.'], 422);
        }

        $key = ApiKey::where('api_key', $request->api_key)
            ->where('status', 'active')
            ->first();

        if (!$key) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        if (!$key->isActive()) {
            return response()->json(['message' => 'API key expired or revoked.'], 401);
        }

        // ── IP Whitelist Enforcement (check BEFORE secret verification) ──
        // If the account has ANY IP whitelist entries, enforcement is active.
        // Only IPs with 'approved' status are allowed through.
        $clientIp = $request->input('client_ip');
        $hasWhitelistEntries = IpWhitelist::where('account_id', $key->account_id)->exists();

        if ($hasWhitelistEntries && $clientIp) {
            $approvedIps = IpWhitelist::where('account_id', $key->account_id)
                ->where('status', 'approved')
                ->pluck('ip_address')
                ->toArray();

            if (!in_array($clientIp, $approvedIps)) {
                $entry = IpWhitelist::where('account_id', $key->account_id)
                    ->where('ip_address', $clientIp)
                    ->first();

                $reason = $entry
                    ? "IP address {$clientIp} is {$entry->status}."
                    : "IP address {$clientIp} is not whitelisted for this account.";

                return response()->json([
                    'message' => $reason,
                    'ip_blocked' => true,
                ], 403);
            }
        }

        if (!Hash::check($request->api_secret, $key->api_secret)) {
            return response()->json(['message' => 'Invalid API secret.'], 401);
        }

        // Update last used
        $key->update(['last_used_at' => now()]);

        // Get account + owner info
        $account = $key->account;
        $owner = $account ? $account->owner : null;

        return response()->json([
            'valid' => true,
            'account_id' => $key->account_id,
            'ip_enforced' => $hasWhitelistEntries,
            'account' => $account ? [
                'id' => $account->id,
                'account_ref' => $account->account_ref,
                'business_name' => $account->business_name,
                'paybill' => $account->paybill,
                'status' => $account->status,
                'rate_limit' => $account->rate_limit ?? 60,
            ] : null,
            'user' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => $owner->role,
                'account_id' => $owner->account_id,
            ] : null,
        ]);
    }
}
