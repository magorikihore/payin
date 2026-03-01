<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BulkEmailNotification extends Notification
{
    use Queueable;

    protected string $emailSubject;
    protected string $greeting;
    protected string $body;
    protected string $actionText;
    protected string $actionUrl;
    protected string $footer;

    public function __construct(array $content)
    {
        $this->emailSubject = $content['subject'] ?? '';
        $this->greeting = $content['greeting'] ?? '';
        $this->body = $content['body'] ?? '';
        $this->actionText = $content['action_text'] ?? '';
        $this->actionUrl = $content['action_url'] ?? '';
        $this->footer = $content['footer'] ?? '';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->name ?? $notifiable->firstname ?? 'there';
        $replace = fn(string $text) => str_replace('{{name}}', $name, $text);

        $mail = (new MailMessage)
            ->subject($replace($this->emailSubject));

        if ($this->greeting) {
            $mail->greeting($replace($this->greeting));
        }

        foreach (explode("\n", $replace($this->body)) as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $mail->line($trimmed);
            }
        }

        if ($this->actionText && $this->actionUrl) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        if ($this->footer) {
            $mail->salutation($replace($this->footer));
        }

        return $mail;
    }
}
