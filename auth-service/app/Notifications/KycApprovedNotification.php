<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
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
        $data = ['name' => $notifiable->name ?? 'there'];
        $tpl = EmailTemplate::getByKey('kyc_approved');

        if ($tpl) {
            $mail = (new MailMessage)
                ->subject(EmailTemplate::replacePlaceholders($tpl->subject, $data))
                ->greeting(EmailTemplate::replacePlaceholders($tpl->greeting, $data));

            foreach (explode("\n", EmailTemplate::replacePlaceholders($tpl->body, $data)) as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') $mail->line($trimmed);
            }

            if ($tpl->action_text && $tpl->action_url) {
                $mail->action($tpl->action_text, $tpl->action_url);
            }

            if ($tpl->footer) {
                $mail->salutation(EmailTemplate::replacePlaceholders($tpl->footer, $data));
            }

            return $mail;
        }

        // Fallback
        return (new MailMessage)
            ->subject('Payin — Account Approved!')
            ->greeting('Great news, ' . $data['name'] . '!')
            ->line('Your KYC verification has been approved. Your account is now active.')
            ->line('You can now generate API keys and start accepting payments.')
            ->action('Go to Dashboard', url('https://login.payin.co.tz/dashboard'))
            ->line('Thank you for choosing Payin.')
            ->salutation('— Payin Team');
    }
}
