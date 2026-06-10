<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SuperAdminOnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingSession
{
    public function handle(Request $request, Closure $next, string $step): Response
    {
        $userId = $request->session()->get('onboarding_user_id');

        if (! $userId) {
            return redirect()->route('login')
                ->with('error', 'Your onboarding session has expired. Please use the link from your invitation email.');
        }

        $user = User::query()->find($userId);

        if (! $user || ! $user->hasRole(UserRole::SuperAdmin->value) || $user->hasCompletedOnboarding()) {
            $request->session()->forget('onboarding_user_id');

            return redirect()->route('login')
                ->with('error', 'This onboarding link is no longer valid.');
        }

        $request->attributes->set('onboarding_user', $user);

        if ($step === 'policy' && ! $user->email_verified_at) {
            return redirect()->to(app(SuperAdminOnboardingService::class)->inviteUrl($user));
        }

        if ($step === 'password' && (! $user->email_verified_at || ! $user->policy_accepted_at)) {
            return redirect()->route('onboarding.policy');
        }

        return $next($request);
    }
}
