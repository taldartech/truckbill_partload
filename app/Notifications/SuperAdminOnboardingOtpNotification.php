<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuperAdminOnboardingOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your TruckBill Email Verification OTP')
            ->view('emails.onboarding.otp', [
                'name' => $notifiable->name,
                'otp' => $this->otp,
                'ttl' => config('onboarding.otp_ttl_minutes'),
            ]);
    }
}
