<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycApprovedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payin — Account Approved!')
            ->greeting('Great news, ' . ($notifiable->name ?? 'there') . '!')
            ->line('Your KYC verification has been approved. Your account is now active.')
            ->line('You can now generate API keys and start accepting payments.')
            ->action('Go to Dashboard', url('https://login.payin.co.tz/dashboard'))
            ->line('Thank you for choosing Payin.')
            ->salutation('— Payin Team');
    }
}
