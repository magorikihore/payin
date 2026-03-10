<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
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
        $data = ['name' => $notifiable->name ?? 'there', 'code' => $this->code];
        $tpl = EmailTemplate::getByKey('two_factor_code');

        if ($tpl) {
            $mail = (new MailMessage)
                ->subject(EmailTemplate::replacePlaceholders($tpl->subject, $data))
                ->greeting(EmailTemplate::replacePlaceholders($tpl->greeting, $data));

            foreach (explode("\n", EmailTemplate::replacePlaceholders($tpl->body, $data)) as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') $mail->line($trimmed);
            }

            if ($tpl->action_text && $tpl->action_url) {
                $mail->action($tpl->action_text, EmailTemplate::replacePlaceholders($tpl->action_url, $data));
            }

            if ($tpl->footer) {
                $mail->salutation(EmailTemplate::replacePlaceholders($tpl->footer, $data));
            }

            return $mail;
        }

        // Fallback
        return (new MailMessage)
            ->subject('Payin — Your Login Verification Code')
            ->greeting('Hello ' . $data['name'] . ',')
            ->line('A sign-in attempt was detected on your account.')
            ->line('Your verification code is:')
            ->line('**' . $this->code . '**')
            ->line('This code expires in 10 minutes.')
            ->line('If you did not attempt to sign in, please change your password immediately.')
            ->salutation('— Payin Team');
    }
}
