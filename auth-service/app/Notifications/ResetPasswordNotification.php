<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payin — Password Reset Code')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('You requested a password reset for your Payin account.')
            ->line('Your verification code is:')
            ->line('**' . $this->code . '**')
            ->line('This code expires in 30 minutes.')
            ->line('If you did not request this, please ignore this email.')
            ->salutation('— Payin Team');
    }
}
