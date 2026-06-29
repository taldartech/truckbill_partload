<?php

namespace App\Support;

use App\Services\TenantContext;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionExpiredResponse
{
    public static function message(): string
    {
        return 'Your subscription has expired. Please renew to continue.';
    }

    public static function staffMessage(): string
    {
        return 'Your subscription has expired. Please contact your super admin to renew.';
    }

    public static function wantsJson(Request $request): bool
    {
        return $request->expectsJson() && ! $request->header('X-Inertia');
    }

    public static function forRequest(Request $request): Response
    {
        if (self::wantsJson($request)) {
            return new JsonResponse(['message' => self::message()], 403);
        }

        return self::redirectForUser($request);
    }

    public static function redirectForUser(Request $request): RedirectResponse
    {
        $context = app(TenantContext::class);

        if ($context->isSuperAdmin()) {
            return redirect()->route('subscription.index')->with('error', self::message());
        }

        return redirect()->route('dashboard')->with('error', self::staffMessage());
    }

    public static function abortUnlessActive(Request $request): void
    {
        if (self::wantsJson($request)) {
            abort(403, self::message());
        }

        throw new HttpResponseException(self::redirectForUser($request));
    }
}
