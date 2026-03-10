<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewIpLoginNotification extends Notification
{
    use Queueable;

    protected string $newIp;
    protected ?string $previousIp;

    public function __construct(string $newIp, ?string $previousIp)
    {
        $this->newIp = $newIp;
        $this->previousIp = $previousIp;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payin — New Login From a Different Location')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('We detected a login to your account from a new IP address.')
            ->line('**New IP:** ' . $this->newIp)
            ->line('**Previous IP:** ' . ($this->previousIp ?? 'First login'))
            ->line('**Time:** ' . now()->format('Y-m-d H:i:s') . ' UTC')
            ->line('If this was you, no action is needed.')
            ->line('If this was NOT you, please change your password immediately and contact support.')
            ->salutation('— Payin Security');
    }
}
