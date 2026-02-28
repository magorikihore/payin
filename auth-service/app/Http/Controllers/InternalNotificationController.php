<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Notifications\TransferApprovedNotification;
use App\Notifications\SettlementApprovedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalNotificationController extends Controller
{
    /**
     * Internal endpoint for other services to trigger email notifications.
     * No auth required — only accessible via internal network.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|integer',
            'type' => 'required|string|in:transfer_approved,settlement_approved',
            'data' => 'required|array',
        ]);

        $account = Account::find($request->account_id);
        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $owner = $account->owner;
        if (!$owner) {
            return response()->json(['message' => 'Account owner not found.'], 404);
        }

        try {
            switch ($request->type) {
                case 'transfer_approved':
                    $owner->notify(new TransferApprovedNotification($request->data));
                    break;

                case 'settlement_approved':
                    $owner->notify(new SettlementApprovedNotification($request->data));
                    break;
            }

            return response()->json(['message' => 'Notification sent.']);
        } catch (\Throwable $e) {
            \Log::warning('Internal notification failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send notification.', 'error' => $e->getMessage()], 500);
        }
    }
}
