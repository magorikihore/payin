<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceEmailNotification extends Notification
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
            'name' => $notifiable->name ?? 'Customer',
        ], $this->details);

        $amount = number_format((float) ($this->details['amount'] ?? 0), 2);
        $currency = $this->details['currency'] ?? 'TZS';

        $tpl = EmailTemplate::getByKey('invoice_email');

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

        $mail = (new MailMessage)
            ->subject('Invoice from ' . ($this->details['business_name'] ?? 'Payin'))
            ->greeting('Hello!')
            ->line('You have received an invoice from **' . ($this->details['business_name'] ?? 'a Payin merchant') . '**.')
            ->line('**Amount:** ' . $amount . ' ' . $currency)
            ->line('**Payment Reference:** ' . ($this->details['reference'] ?? 'N/A'))
            ->line('**Paybill/Till:** ' . ($this->details['paybill'] ?? 'N/A'));

        if (!empty($this->details['description'])) {
            $mail->line('**Description:** ' . $this->details['description']);
        }

        if (!empty($this->details['expires_at'])) {
            $mail->line('**Expires:** ' . $this->details['expires_at']);
        }

        $mail->line('Please use the reference above when making your payment.')
            ->salutation('— Payin Team');

        return $mail;
    }
}
