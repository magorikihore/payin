<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
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
        $data = [
            'name' => $notifiable->name ?? 'there',
            'reason' => $this->notes ? '**Reason:** ' . $this->notes : '',
        ];
        $tpl = EmailTemplate::getByKey('kyc_rejected');

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
        $mail = (new MailMessage)
            ->subject('Payin — KYC Verification Update')
            ->greeting('Hello ' . $data['name'] . ',')
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
