<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
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
        $data = ['name' => $notifiable->name ?? 'there'];
        $tpl = EmailTemplate::getByKey('welcome');

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
            ->subject('Welcome to Payin!')
            ->greeting('Welcome, ' . $data['name'] . '!')
            ->line('Thank you for creating your Payin account.')
            ->line('To get started, please complete your KYC verification so we can activate your account.')
            ->action('Complete KYC', url('https://login.payin.co.tz/kyc'))
            ->line('Once verified, you\'ll be able to generate API keys and start accepting mobile money payments.')
            ->salutation('— Payin Team');
    }
}
