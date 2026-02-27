<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycRejectedNotification extends Notification
{
    use Queueable;

    public string $notes;

    public function __construct(string $notes = '')
    {
        $this->notes = $notes;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Payin — KYC Verification Update')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('Your KYC verification could not be approved at this time.');

        if ($this->notes) {
            $mail->line('**Reason:** ' . $this->notes);
        }

        return $mail
            ->line('Please update your KYC information and resubmit.')
            ->action('Update KYC', url('https://login.payin.co.tz/kyc'))
            ->salutation('— Payin Team');
    }
}
