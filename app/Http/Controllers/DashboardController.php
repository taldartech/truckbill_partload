<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Bilty;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\InwardRegister;
use App\Models\MainChallan;
use App\Models\Truck;
use App\Models\User;
use App\Models\Voucher;
use App\Services\TenantContext;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $context = app(TenantContext::class);
        $user = request()->user();
        $welcome = $this->welcomeData($context, $user);

        if ($context->isMasterAdmin()) {
            return Inertia::render('Dashboard', [
                'dashboard' => [
                    'mode' => 'master_admin',
                    'welcome' => $welcome,
                    'stats' => [
                        'super_admins' => User::role(UserRole::SuperAdmin->value)->count(),
                        'companies' => Company::count(),
                        'active_subscriptions' => CompanySubscription::query()
                            ->whereIn('status', ['active', 'trial'])
                            ->whereDate('ends_on', '>=', now()->toDateString())
                            ->count(),
                    ],
                ],
            ]);
        }

        if ($context->isSuperAdmin() && ! $context->isActingAsAdmin()) {
            $companyIds = $context->ownedCompanyIds();

            return Inertia::render('Dashboard', [
                'dashboard' => [
                    'mode' => 'super_admin_setup',
                    'welcome' => $welcome,
                    'stats' => [
                        'companies' => count($companyIds),
                        'branches' => Branch::whereIn('company_id', $companyIds)->count(),
                        'users' => User::whereIn('company_id', $companyIds)->count(),
                    ],
                ],
            ]);
        }

        return Inertia::render('Dashboard', [
            'dashboard' => [
                'mode' => 'operational',
                'welcome' => $welcome,
                'stats' => $this->operationalStats($context),
                'recent_bilties' => $this->recentBilties($context),
                'recent_challans' => $this->recentChallans($context),
            ],
        ]);
    }

    private function welcomeData(TenantContext $context, User $user): array
    {
        $role = $user->getRoleNames()->first();
        $roleEnum = $role ? UserRole::tryFrom($role) : null;

        if ($context->isSuperAdmin() && $context->isActingAsAdmin()) {
            $roleLabel = UserRole::Admin->label();
            $roleKey = UserRole::Admin->value;
        } else {
            $roleLabel = $roleEnum?->label() ?? 'User';
            $roleKey = $role ?? 'user';
        }

        $subtitle = match (true) {
            $context->isMasterAdmin() => 'Manage platform users, roles, and subscriptions.',
            $context->isSuperAdmin() && ! $context->isActingAsAdmin() => 'Set up companies, branches, and users. Select a branch when you are ready for daily operations.',
            $roleKey === UserRole::Moderator->value => 'Create and manage daily transport transactions for your branch.',
            $roleKey === UserRole::Accountant->value => 'Review records, vouchers, and reports for your branch.',
            default => 'Overview of transport operations for your branch.',
        };

        return [
            'name' => $user->name,
            'role_label' => $roleLabel,
            'role_key' => $roleKey,
            'subtitle' => $subtitle,
            'company' => $context->activeCompany()?->name ?? $user->company?->name,
            'branch' => $context->activeBranch()?->name ?? $user->branch?->name,
            'date' => Carbon::now()->format('l, j F Y'),
        ];
    }

    private function operationalStats(TenantContext $context): array
    {
        $today = Carbon::today();
        $stats = [];

        if ($context->can('view bilties')) {
            $stats['bilties'] = [
                'total' => Bilty::count(),
                'today' => Bilty::whereDate('date', $today)->count(),
                'pending' => Bilty::where('status', 'Pending')->count(),
            ];
        }

        if ($context->can('view trucks')) {
            $stats['trucks'] = [
                'available' => Truck::where('status', 'Available')->count(),
                'total' => Truck::count(),
            ];
        }

        if ($context->can('view challans')) {
            $stats['challans'] = [
                'total' => Challan::count(),
                'today' => Challan::whereDate('date', $today)->count(),
            ];
            $stats['main_challans'] = [
                'total' => MainChallan::count(),
                'today' => MainChallan::whereDate('date', $today)->count(),
            ];
        }

        if ($context->can('view inward_registers')) {
            $stats['inward_registers'] = [
                'total' => InwardRegister::count(),
                'today' => InwardRegister::whereDate('date', $today)->count(),
            ];
        }

        if ($context->can('view vouchers')) {
            $stats['vouchers'] = [
                'total' => Voucher::count(),
                'today' => Voucher::whereDate('date', $today)->count(),
            ];
        }

        return $stats;
    }

    private function recentBilties(TenantContext $context): array
    {
        if (! $context->can('view bilties')) {
            return [];
        }

        return Bilty::with('consignee:id,name')
            ->orderByDesc('id')
            ->take(5)
            ->get(['id', 'bilty_number', 'date', 'consignee_id', 'status', 'amount'])
            ->toArray();
    }

    private function recentChallans(TenantContext $context): array
    {
        if (! $context->can('view challans')) {
            return [];
        }

        return Challan::with('truck:id,truck_number')
            ->orderByDesc('id')
            ->take(5)
            ->get(['id', 'challan_number', 'date', 'truck_id'])
            ->toArray();
    }
}
