<?php

namespace App\Http\Controllers;

use App\Models\CryptoWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoWalletController extends Controller
{
    /**
     * List crypto wallets for the authenticated user's account.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $wallets = CryptoWallet::where('account_id', $user->account_id)
            ->orderByDesc('is_default')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['crypto_wallets' => $wallets]);
    }

    /**
     * Add a new crypto wallet.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only owner or admin can manage crypto wallets.'], 403);
        }

        $request->validate([
            'currency' => 'required|string|max:50',
            'network' => 'required|string|max:100',
            'wallet_address' => 'required|string|max:500',
            'label' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        $accountId = $user->account_id;

        $isDefault = $request->boolean('is_default', false);
        $existingCount = CryptoWallet::where('account_id', $accountId)->count();

        if ($existingCount === 0) {
            $isDefault = true;
        }

        if ($isDefault) {
            CryptoWallet::where('account_id', $accountId)->update(['is_default' => false]);
        }

        $wallet = CryptoWallet::create([
            'account_id' => $accountId,
            'currency' => $request->currency,
            'network' => $request->network,
            'wallet_address' => $request->wallet_address,
            'label' => $request->label,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'message' => 'Crypto wallet added.',
            'crypto_wallet' => $wallet,
        ], 201);
    }

    /**
     * Set a crypto wallet as default.
     */
    public function setDefault(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only owner or admin can manage crypto wallets.'], 403);
        }

        $wallet = CryptoWallet::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (!$wallet) {
            return response()->json(['message' => 'Crypto wallet not found.'], 404);
        }

        CryptoWallet::where('account_id', $user->account_id)->update(['is_default' => false]);
        $wallet->update(['is_default' => true]);

        return response()->json(['message' => 'Default crypto wallet updated.', 'crypto_wallet' => $wallet]);
    }

    /**
     * Delete a crypto wallet.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Only owner or admin can manage crypto wallets.'], 403);
        }

        $wallet = CryptoWallet::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (!$wallet) {
            return response()->json(['message' => 'Crypto wallet not found.'], 404);
        }

        $wasDefault = $wallet->is_default;
        $wallet->delete();

        if ($wasDefault) {
            $first = CryptoWallet::where('account_id', $user->account_id)->first();
            if ($first) {
                $first->update(['is_default' => true]);
            }
        }

        return response()->json(['message' => 'Crypto wallet removed.']);
    }
}
