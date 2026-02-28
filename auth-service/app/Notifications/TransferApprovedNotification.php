<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferApprovedNotification extends Notification
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

        $tpl = EmailTemplate::getByKey('transfer_approved');

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
            ->subject('Payin — Internal Transfer Approved')
            ->greeting('Hello, ' . $data['name'] . '!')
            ->line('Your internal transfer has been approved and executed.')
            ->line('**Reference:** ' . ($this->details['reference'] ?? 'N/A'))
            ->line('**Operator:** ' . ($this->details['operator'] ?? 'N/A'))
            ->line('**Amount:** ' . $amount . ' ' . $currency)
            ->line('Funds have been moved from your Collection wallet to your Disbursement wallet.')
            ->line('You can view the updated balances on your dashboard.')
            ->salutation('— Payin Team');
    }
}
