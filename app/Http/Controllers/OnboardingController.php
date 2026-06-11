<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SuperAdminOnboardingService;
use App\Services\SingleDeviceLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly SuperAdminOnboardingService $onboarding,
        private readonly SingleDeviceLoginService $singleDeviceLogin,
    ) {}

    public function verify(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        if (! $user->hasRole(UserRole::SuperAdmin->value)) {
            abort(404);
        }

        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('login')
                ->with('success', 'Your account is already set up. Please sign in.');
        }

        if ($user->email_verified_at) {
            $request->session()->put('onboarding_user_id', $user->id);

            return redirect()->route('onboarding.policy');
        }

        return Inertia::render('Auth/Onboarding/VerifyEmail', [
            'user' => $user->only(['id', 'name', 'email']),
            'verify_url' => route('onboarding.verify-otp', ['user' => $user->id]),
            'send_otp_url' => URL::temporarySignedRoute(
                'onboarding.send-otp',
                now()->addDays(config('onboarding.invite_ttl_days')),
                ['user' => $user->id]
            ),
            'has_active_otp' => $this->onboarding->hasActiveOtp($user),
        ]);
    }

    public function verifyOtp(Request $request, User $user)
    {
        if (! $user->hasRole(UserRole::SuperAdmin->value) || $user->hasCompletedOnboarding()) {
            abort(404);
        }

        $validated = $request->validate([
            'otp' => 'required|digits:'.config('onboarding.otp_length'),
        ]);

        if (! $this->onboarding->verifyOtp($user, $validated['otp'])) {
            return back()->withErrors([
                'otp' => 'The OTP is invalid or has expired. Please try again or request a new one.',
            ]);
        }

        $request->session()->put('onboarding_user_id', $user->id);

        return redirect()->route('onboarding.policy');
    }

    public function sendOtp(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        if (! $user->hasRole(UserRole::SuperAdmin->value) || $user->hasCompletedOnboarding()) {
            abort(404);
        }

        if ($user->email_verified_at) {
            return redirect()->route('onboarding.policy');
        }

        $this->onboarding->sendOtp($user);

        return back()->with('success', 'An OTP has been sent to your email.');
    }

    public function policy(Request $request)
    {
        $user = $request->attributes->get('onboarding_user');

        return Inertia::render('Auth/Onboarding/AcceptPolicies', [
            'user' => $user->only(['id', 'name', 'email']),
            'policies' => $this->onboarding->policies(),
            'policy_version' => config('onboarding.policy_version'),
            'submit_url' => route('onboarding.accept-policy'),
        ]);
    }

    public function acceptPolicy(Request $request)
    {
        $user = $request->attributes->get('onboarding_user');

        $policyKeys = collect($this->onboarding->policies())->pluck('key')->all();

        $rules = [];
        foreach ($policyKeys as $key) {
            $rules['policies.'.$key] = 'accepted';
        }

        $request->validate($rules, [
            'policies.*.accepted' => 'You must accept all policies to continue.',
        ]);

        $this->onboarding->acceptPolicies($user);

        return redirect()->route('onboarding.password');
    }

    public function password(Request $request)
    {
        $user = $request->attributes->get('onboarding_user');

        return Inertia::render('Auth/Onboarding/SetPassword', [
            'user' => $user->only(['id', 'name', 'email']),
            'submit_url' => route('onboarding.set-password'),
        ]);
    }

    public function setPassword(Request $request)
    {
        $user = $request->attributes->get('onboarding_user');

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->onboarding->complete($user, $validated['password']);

        $request->session()->forget('onboarding_user_id');

        Auth::login($user);
        $request->session()->regenerate();

        $this->singleDeviceLogin->enforce(
            $user,
            $request->session()->getId(),
            false,
        );

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to TruckBill! Your account is ready.');
    }
}
