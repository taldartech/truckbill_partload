<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user() ? $request->user()->getRoleNames() : [],
                'permissions' => $request->user() ? $request->user()->getAllPermissions()->pluck('name') : [],
                'is_admin' => $request->user() ? $request->user()->hasRole(['master_admin', 'super_admin']) : false,
                'company_name' => $request->user() && $request->user()->company ? $request->user()->company->name : 'Demo Company',
                'subscription_ends_at' => function () use ($request) {
                    if ($request->user() && $request->user()->company_id) {
                        $sub = \Illuminate\Support\Facades\DB::table('subscriptions')
                            ->where('company_id', $request->user()->company_id)
                            ->where('status', 'active')
                            ->orderBy('ends_at', 'desc')
                            ->first();
                        if ($sub) {
                            return \Carbon\Carbon::parse($sub->ends_at)->format('d M, Y');
                        }
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
