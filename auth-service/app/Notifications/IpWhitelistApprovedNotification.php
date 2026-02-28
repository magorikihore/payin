<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IpWhitelistApprovedNotification extends Notification
{
    use Queueable;

    protected string $ipAddress;
    protected ?string $label;

    public function __construct(string $ipAddress, ?string $label = null)
    {
        $this->ipAddress = $ipAddress;
        $this->label = $label;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = [
            'name' => $notifiable->name ?? 'there',
            'ip_address' => $this->ipAddress,
            'label' => $this->label ?? 'N/A',
            'business_name' => $notifiable->account->business_name ?? '',
        ];

        $tpl = EmailTemplate::getByKey('ip_whitelist_approved');

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
            ->subject('Payin — IP Address Approved')
            ->greeting('Hello, ' . $data['name'] . '!')
            ->line('Your IP whitelist request has been approved.')
            ->line('**IP Address:** ' . $this->ipAddress)
            ->line('**Label:** ' . ($this->label ?? 'N/A'))
            ->line('This IP address is now authorized to make API requests for your account.')
            ->line('If you did not request this, please contact support immediately.')
            ->salutation('— Payin Team');
    }
}
