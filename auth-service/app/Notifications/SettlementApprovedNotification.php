<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementApprovedNotification extends Notification
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
            'name' => $notifiable->name ?? 'there',
            'business_name' => $notifiable->account->business_name ?? '',
        ], $this->details);

        $tpl = EmailTemplate::getByKey('settlement_approved');

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
            ->subject('Payin — Settlement Approved')
            ->greeting('Hello, ' . $data['name'] . '!')
            ->line('Your settlement request has been approved.')
            ->line('**Settlement Ref:** ' . ($this->details['settlement_ref'] ?? 'N/A'))
            ->line('**Operator:** ' . ($this->details['operator'] ?? 'N/A'))
            ->line('**Amount:** ' . $amount . ' ' . $currency)
            ->line('**Bank:** ' . ($this->details['bank_name'] ?? 'N/A'))
            ->line('**Account:** ' . ($this->details['account_number'] ?? 'N/A') . ' (' . ($this->details['account_name'] ?? '') . ')')
            ->line('The funds will be disbursed to your bank account shortly.')
            ->salutation('— Payin Team');
    }
}
