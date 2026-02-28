<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountOpeningNotification extends Notification
{
    use Queueable;

    protected string $password;

    public function __construct(string $password)
    {
        $this->password = $password;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = [
            'name' => $notifiable->firstname ?? $notifiable->name ?? 'there',
            'email' => $notifiable->email,
            'password' => $this->password,
            'business_name' => $notifiable->account->business_name ?? '',
            'account_ref' => $notifiable->account->account_ref ?? '',
        ];

        $tpl = EmailTemplate::getByKey('account_opening');

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
            ->subject('Your Payin Business Account Has Been Created')
            ->greeting('Hello ' . $data['name'] . ',')
            ->line('A Payin business account has been created for **' . $data['business_name'] . '**.')
            ->line('Here are your login credentials:')
            ->line('**Email:** ' . $data['email'])
            ->line('**Password:** ' . $this->password)
            ->line('**Account Reference:** ' . $data['account_ref'])
            ->action('Login to Payin', url('https://login.payin.co.tz/login'))
            ->line('Please change your password after your first login for security.')
            ->line('If you have any questions, feel free to contact our support team.')
            ->salutation('— Payin Team');
    }
}
