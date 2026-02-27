<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Payin!')
            ->greeting('Welcome, ' . ($notifiable->name ?? 'there') . '!')
            ->line('Thank you for creating your Payin account.')
            ->line('To get started, please complete your KYC verification so we can activate your account.')
            ->action('Complete KYC', url('https://login.payin.co.tz/kyc'))
            ->line('Once verified, you\'ll be able to generate API keys and start accepting mobile money payments.')
            ->salutation('— Payin Team');
    }
}
