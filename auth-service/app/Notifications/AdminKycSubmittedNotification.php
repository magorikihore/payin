<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminKycSubmittedNotification extends Notification
{
    use Queueable;

    protected array $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = array_merge([
            'admin_name' => $notifiable->firstname ?? $notifiable->name ?? 'Admin',
        ], $this->details);

        $tpl = EmailTemplate::getByKey('admin_kyc_submitted');

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

        return (new MailMessage)
            ->subject('Payin — New KYC Submission Requires Review')
            ->greeting('Hello, ' . $data['admin_name'] . '!')
            ->line('A business has submitted KYC details for review.')
            ->line('**Business:** ' . ($this->details['business_name'] ?? 'N/A'))
            ->line('**Account Ref:** ' . ($this->details['account_ref'] ?? 'N/A'))
            ->line('**Submitted By:** ' . ($this->details['submitted_by'] ?? 'N/A'))
            ->line('Please log in to the admin panel to review and approve or reject this KYC submission.')
            ->action('Review KYC', config('app.admin_url', 'https://api.payin.co.tz/admin'))
            ->salutation('— Payin System');
    }
}
