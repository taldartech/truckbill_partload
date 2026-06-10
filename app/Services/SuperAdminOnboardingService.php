<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserOnboarding;
use App\Notifications\SuperAdminOnboardingNotification;
use App\Notifications\SuperAdminOnboardingOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SuperAdminOnboardingService
{
    public function invite(User $user): void
    {
        $user->forceFill([
            'policy_accepted_at' => null,
            'policy_version' => null,
            'email_verified_at' => null,
        ])->save();

        $this->onboardingFor($user)->update([
            'otp_hash' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'completed_at' => null,
        ]);

        $user->notify(new SuperAdminOnboardingNotification($this->inviteUrl($user)));
    }

    public function sendOtp(User $user): void
    {
        $otp = $this->generateOtp();
        $onboarding = $this->onboardingFor($user);

        $onboarding->update([
            'otp_hash' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(config('onboarding.otp_ttl_minutes')),
            'otp_attempts' => 0,
        ]);

        $user->notify(new SuperAdminOnboardingOtpNotification($otp));
    }

    public function hasActiveOtp(User $user): bool
    {
        return (bool) $user->onboarding?->hasActiveOtp();
    }

    public function inviteUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'onboarding.verify',
            now()->addDays(config('onboarding.invite_ttl_days')),
            ['user' => $user->id]
        );
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        $onboarding = $user->onboarding;

        if (! $onboarding) {
            return false;
        }

        if ($onboarding->otp_attempts >= config('onboarding.otp_max_attempts')) {
            return false;
        }

        if (! $onboarding->otp_expires_at || $onboarding->otp_expires_at->isPast()) {
            return false;
        }

        if (! $onboarding->otp_hash || ! Hash::check($otp, $onboarding->otp_hash)) {
            $onboarding->increment('otp_attempts');

            return false;
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $onboarding->update([
            'otp_hash' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
        ]);

        return true;
    }

    public function acceptPolicies(User $user): void
    {
        $user->forceFill([
            'policy_accepted_at' => now(),
            'policy_version' => config('onboarding.policy_version'),
        ])->save();
    }

    public function complete(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->onboardingFor($user)->update([
            'otp_hash' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'completed_at' => now(),
        ]);
    }

    public function generateOtp(): string
    {
        $length = config('onboarding.otp_length');

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    public function unusablePassword(): string
    {
        return Hash::make(Str::random(64));
    }

    public function policies(): array
    {
        return config('onboarding.policies', []);
    }

    private function onboardingFor(User $user): UserOnboarding
    {
        return $user->onboarding()->firstOrCreate([
            'user_id' => $user->id,
        ]);
    }
}
