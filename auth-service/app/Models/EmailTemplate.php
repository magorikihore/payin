<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'subject', 'greeting', 'body',
        'action_text', 'action_url', 'footer', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get a template by key, or null if not found / inactive.
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Replace {{placeholders}} in a string with actual values.
     */
    public static function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }

    /**
     * Default templates (used as fallback and for seeding).
     */
    public static function defaults(): array
    {
        return [
            [
                'key' => 'welcome',
                'name' => 'Welcome Email',
                'subject' => 'Welcome to Payin!',
                'greeting' => 'Welcome, {{name}}!',
                'body' => "Thank you for creating your Payin account.\n\nTo get started, please complete your KYC verification so we can activate your account.\n\nOnce verified, you'll be able to generate API keys and start accepting mobile money payments.",
                'action_text' => 'Complete KYC',
                'action_url' => 'https://login.payin.co.tz/kyc',
                'footer' => '— Payin Team',
                'is_active' => true,
            ],
            [
                'key' => 'password_reset',
                'name' => 'Password Reset Code',
                'subject' => 'Payin — Password Reset Code',
                'greeting' => 'Hello {{name}},',
                'body' => "You requested a password reset for your Payin account.\n\nYour verification code is:\n\n**{{code}}**\n\nThis code expires in 30 minutes.\n\nIf you did not request this, please ignore this email.",
                'action_text' => null,
                'action_url' => null,
                'footer' => '— Payin Team',
                'is_active' => true,
            ],
            [
                'key' => 'kyc_approved',
                'name' => 'KYC Approved',
                'subject' => 'Payin — Account Approved!',
                'greeting' => 'Great news, {{name}}!',
                'body' => "Your KYC verification has been approved. Your account is now active.\n\nYou can now generate API keys and start accepting payments.\n\nThank you for choosing Payin.",
                'action_text' => 'Go to Dashboard',
                'action_url' => 'https://login.payin.co.tz/dashboard',
                'footer' => '— Payin Team',
                'is_active' => true,
            ],
            [
                'key' => 'kyc_rejected',
                'name' => 'KYC Rejected',
                'subject' => 'Payin — KYC Verification Update',
                'greeting' => 'Hello {{name}},',
                'body' => "Your KYC verification could not be approved at this time.\n\n{{reason}}\n\nPlease update your KYC information and resubmit.",
                'action_text' => 'Update KYC',
                'action_url' => 'https://login.payin.co.tz/kyc',
                'footer' => '— Payin Team',
                'is_active' => true,
            ],
        ];
    }
}
