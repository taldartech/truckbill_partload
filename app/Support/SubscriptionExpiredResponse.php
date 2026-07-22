<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionExpiredResponse
{
    public static function forGraceRestrictedAction(Request $request, ?Company $company = null): RedirectResponse
    {
        return redirect()
            ->route('dashboard')
            ->with('error', 'Your subscription is in a read-only grace period. Writing actions like creating a branch are blocked until you renew.');
    }

    public static function forLocked(Request $request): RedirectResponse
    {
        return redirect()
            ->route('dashboard')
            ->with('error', 'An active subscription is required to continue. Start a trial or renew the company plan.');
    }

    public static function abortUnlessCanMutate(Request $request, ?Company $company = null): void
    {
        $payload = $company?->subscriptionAccessPayload() ?? [];
        $mode = $payload['access_mode'] ?? 'locked';

        if ($mode === 'grace') {
            throw new HttpResponseException(self::forGraceRestrictedAction($request, $company));
        }

        throw new HttpResponseException(self::forLocked($request));
    }
}
