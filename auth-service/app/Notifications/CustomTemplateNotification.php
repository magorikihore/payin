<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomTemplateNotification extends Notification
{
    use Queueable;

    public EmailTemplate $template;

    public function __construct(EmailTemplate $template)
    {
        $this->template = $template;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = ['name' => $notifiable->name ?? 'there'];
        $tpl = $this->template;

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
}
