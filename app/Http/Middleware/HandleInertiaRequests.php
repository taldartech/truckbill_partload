<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $context = app(TenantContext::class);
        $user = $request->user();
        $activeBranch = $context->activeBranch();
        if ($activeBranch) {
            $activeBranch->loadMissing('station:id,name');
        }
        $activeCompany = $context->activeCompany();

        return array_merge(parent::share($request), [
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $user,
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'effective_permissions' => $context->effectivePermissions(),
                'is_master_admin' => $context->isMasterAdmin(),
                'is_super_admin' => $context->isSuperAdmin(),
                'is_admin' => $context->isPlatformAdmin(),
                'is_acting_as_admin' => $context->isActingAsAdmin(),
                'is_portal_user' => $context->isPortalUser(),
                'company_name' => $activeCompany?->name
                    ?? ($user && $user->company ? $user->company->name : 'Demo Company'),
                'branch_name' => $activeBranch?->name ?? ($user?->branch?->name),
                'active_branch' => $activeBranch ? [
                    'id' => $activeBranch->id,
                    'name' => $activeBranch->name,
                    'station_id' => $activeBranch->station_id,
                    'station_name' => $activeBranch->station?->name,
                ] : null,
                'active_company' => $activeCompany ? $activeCompany->only(['id', 'name']) : null,
                'subscription_ends_at' => function () use ($request, $context) {
                    $companyId = $context->activeCompanyId() ?? $request->user()?->company_id;

                    if (! $companyId) {
                        return null;
                    }

                    $sub = \Illuminate\Support\Facades\DB::table('subscriptions')
                        ->where('company_id', $companyId)
                        ->where('status', 'active')
                        ->orderBy('ends_at', 'desc')
                        ->first();

                    if ($sub) {
                        return \Carbon\Carbon::parse($sub->ends_at)->format('d M, Y');
                    }

                    return null;
                },
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
