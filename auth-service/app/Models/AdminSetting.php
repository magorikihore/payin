<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, mixed $value): static
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get the notification email addresses as an array.
     */
    public static function getNotificationEmails(): array
    {
        $val = static::getValue('notification_emails');
        if (!$val) return [];
        $decoded = json_decode($val, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set the notification email addresses.
     */
    public static function setNotificationEmails(array $emails): void
    {
        static::setValue('notification_emails', json_encode(array_values(array_unique($emails))));
    }
}
