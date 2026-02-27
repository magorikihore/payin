<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link to the given email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Return success even if email not found to prevent enumeration
            return response()->json([
                'message' => 'If a matching account is found, a password reset link will be sent to your email.',
            ]);
        }

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Generate a 6-digit OTP code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store the token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($code),
            'created_at' => Carbon::now(),
        ]);

        // Send notification
        $user->notify(new ResetPasswordNotification($code));

        return response()->json([
            'message' => 'If a matching account is found, a password reset link will be sent to your email.',
        ]);
    }

    /**
     * Verify the reset code.
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired reset code.'], 422);
        }

        // Check if token is expired (30 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(30)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Reset code has expired. Please request a new one.'], 422);
        }

        if (!Hash::check($request->code, $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset code.'], 422);
        }

        return response()->json(['message' => 'Code verified successfully.', 'verified' => true]);
    }

    /**
     * Reset the password using code + new password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired reset code.'], 422);
        }

        // Check if token is expired (30 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(30)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Reset code has expired. Please request a new one.'], 422);
        }

        if (!Hash::check($request->code, $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset code.'], 422);
        }

        // Update user password
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully. You can now sign in.']);
    }
}
