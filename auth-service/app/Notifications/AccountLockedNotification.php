<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLockedNotification extends Notification
{
    use Queueable;

    protected string $ip;
    protected bool $isAdminAlert;

    public function __construct(string $ip, bool $isAdminAlert = false)
    {
        $this->ip = $ip;
        $this->isAdminAlert = $isAdminAlert;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isAdminAlert) {
            return (new MailMessage)
                ->subject('Payin — Security Alert: Account Locked')
                ->greeting('Security Alert')
                ->line('The following account has been locked due to too many failed login attempts:')
                ->line('**User:** ' . ($notifiable->routeNotificationFor('mail') ?? 'Unknown'))
                ->line('**IP Address:** ' . $this->ip)
                ->line('**Time:** ' . now()->format('Y-m-d H:i:s') . ' UTC')
                ->line('This may indicate a brute-force attack. Please investigate if necessary.')
                ->salutation('— Payin Security');
        }

        return (new MailMessage)
            ->subject('Payin — Your Account Has Been Locked')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('Your account has been temporarily locked due to too many failed login attempts.')
            ->line('**IP Address:** ' . $this->ip)
            ->line('**Time:** ' . now()->format('Y-m-d H:i:s') . ' UTC')
            ->line('Your account will be unlocked automatically after 30 minutes.')
            ->line('If this was not you, we recommend changing your password immediately after the lockout period ends.')
            ->salutation('— Payin Security');
    }
}
