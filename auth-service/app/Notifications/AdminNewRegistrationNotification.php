<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNewRegistrationNotification extends Notification
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
        $adminName = $notifiable->firstname ?? $notifiable->name ?? 'Admin';
        $businessName = $this->details['business_name'] ?? 'N/A';
        $ownerName = $this->details['owner_name'] ?? 'N/A';
        $email = $this->details['email'] ?? 'N/A';
        $country = $this->details['country'] ?? 'N/A';
        $accountRef = $this->details['account_ref'] ?? 'N/A';

        return (new MailMessage)
            ->subject('Payin — New User Registration')
            ->greeting('Hello, ' . $adminName . '!')
            ->line('A new user has just registered on the platform.')
            ->line('**Business Name:** ' . $businessName)
            ->line('**Owner:** ' . $ownerName)
            ->line('**Email:** ' . $email)
            ->line('**Country:** ' . $country)
            ->line('**Account Ref:** ' . $accountRef)
            ->line('The account is pending KYC submission and approval.')
            ->action('View in Admin Panel', config('app.admin_url', config('app.url', 'https://api.payin.co.tz')))
            ->salutation('— Payin Notification System');
    }
}
