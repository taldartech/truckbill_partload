<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\SubscriptionExpiredResponse;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $companyId = $context->activeCompanyId();

        if (! $companyId) {
            return $next($request);
        }

        $company = Company::query()
            ->with('subscription')
            ->find($companyId);

        if ($company && ! $company->isPlanActive()) {
            return SubscriptionExpiredResponse::forRequest($request);
        }

        return $next($request);
    }
}
