<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminSettlementRequestedNotification extends Notification
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

        $tpl = EmailTemplate::getByKey('admin_settlement_requested');

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

        $amount = number_format((float) ($this->details['amount'] ?? 0), 2);
        $currency = $this->details['currency'] ?? 'TZS';

        return (new MailMessage)
            ->subject('Payin — New Settlement Request Requires Approval')
            ->greeting('Hello, ' . $data['admin_name'] . '!')
            ->line('A new settlement request has been submitted and requires your approval.')
            ->line('**Business:** ' . ($this->details['business_name'] ?? 'N/A'))
            ->line('**Settlement Ref:** ' . ($this->details['settlement_ref'] ?? 'N/A'))
            ->line('**Amount:** ' . $amount . ' ' . $currency)
            ->line('**Operator:** ' . ($this->details['operator'] ?? 'N/A'))
            ->line('**Bank:** ' . ($this->details['bank_name'] ?? 'N/A') . ' — ' . ($this->details['account_number'] ?? 'N/A'))
            ->line('Please log in to the admin panel to review and approve this settlement.')
            ->action('Review Settlements', config('app.admin_url', 'https://api.payin.co.tz/admin'))
            ->salutation('— Payin System');
    }
}
