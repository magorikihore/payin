<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FailedTwoFactorNotification extends Notification
{
    use Queueable;

    protected string $ip;
    protected int $attempts;

    public function __construct(string $ip, int $attempts)
    {
        $this->ip = $ip;
        $this->attempts = $attempts;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payin — Suspicious Login Attempt on Your Account')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('Someone has entered incorrect verification codes on your account multiple times.')
            ->line('**Failed attempts:** ' . $this->attempts)
            ->line('**IP Address:** ' . $this->ip)
            ->line('**Time:** ' . now()->format('Y-m-d H:i:s') . ' UTC')
            ->line('If this was not you, please change your password immediately.')
            ->salutation('— Payin Security');
    }
}
