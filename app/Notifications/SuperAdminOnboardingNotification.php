<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuperAdminOnboardingNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $onboardingUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your TruckBill Onboarding Link')
            ->view('emails.onboarding.invite', [
                'name' => $notifiable->name,
                'onboardingUrl' => $this->onboardingUrl,
            ]);
    }
}
