<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use App\Notifications\TransferApprovedNotification;
use App\Notifications\SettlementApprovedNotification;
use App\Notifications\AdminSettlementRequestedNotification;
use App\Notifications\AdminTransferRequestedNotification;
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
            'type' => 'required|string|in:transfer_approved,settlement_approved,settlement_requested,transfer_requested',
            'data' => 'required|array',
        ]);

        $account = Account::find($request->account_id);
        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        try {
            switch ($request->type) {
                case 'transfer_approved':
                case 'settlement_approved':
                    $owner = $account->owner;
                    if (!$owner) {
                        return response()->json(['message' => 'Account owner not found.'], 404);
                    }
                    if ($request->type === 'transfer_approved') {
                        $owner->notify(new TransferApprovedNotification($request->data));
                    } else {
                        $owner->notify(new SettlementApprovedNotification($request->data));
                    }
                    break;

                case 'settlement_requested':
                case 'transfer_requested':
                    $data = array_merge($request->data, [
                        'business_name' => $account->business_name ?? 'N/A',
                        'account_ref' => $account->account_ref ?? 'N/A',
                    ]);
                    $notification = $request->type === 'settlement_requested'
                        ? new AdminSettlementRequestedNotification($data)
                        : new AdminTransferRequestedNotification($data);
                    $this->notifyAdmins($notification);
                    break;
            }

            return response()->json(['message' => 'Notification sent.']);
        } catch (\Throwable $e) {
            \Log::warning('Internal notification failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send notification.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send a notification to all admin-level users (super_admin + admin_user).
     */
    private function notifyAdmins($notification): void
    {
        $admins = User::whereIn('role', ['super_admin', 'admin_user'])->get();
        foreach ($admins as $admin) {
            try {
                $admin->notify($notification);
            } catch (\Throwable $e) {
                \Log::warning('Failed to notify admin ' . $admin->email . ': ' . $e->getMessage());
            }
        }
    }
}
