<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Bilty;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\Company;
use App\Models\Truck;
use App\Models\User;
use App\Models\Voucher;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $context = app(TenantContext::class);

        if ($context->isMasterAdmin()) {
            return Inertia::render('Dashboard', [
                'portal_mode' => 'master_admin',
                'portal_stats' => [
                    'super_admins' => User::role(UserRole::SuperAdmin->value)->count(),
                    'companies' => Company::count(),
                    'active_subscriptions' => DB::table('subscriptions')->where('status', 'active')->count(),
                ],
            ]);
        }

        if ($context->isSuperAdmin() && ! $context->isActingAsAdmin()) {
            $companyIds = $context->ownedCompanyIds();

            return Inertia::render('Dashboard', [
                'portal_mode' => 'super_admin',
                'portal_stats' => [
                    'companies' => count($companyIds),
                    'branches' => Branch::whereIn('company_id', $companyIds)->count(),
                    'users' => User::whereIn('company_id', $companyIds)->count(),
                ],
            ]);
        }

        $today = Carbon::today();

        $stats = [
            'total_bilties' => Bilty::count(),
            'today_bilties' => Bilty::whereDate('date', $today)->count(),
            'available_trucks' => Truck::where('status', 'Available')->count(),
            'total_challans' => Challan::count(),
            'today_challans' => Challan::whereDate('date', $today)->count(),
            'pending_bilties' => Bilty::where('status', 'Pending')->count(),
            'total_vouchers' => Voucher::count(),
            'recent_bilties' => Bilty::with('consignee')->orderBy('id', 'desc')->take(5)->get(),
        ];

        return Inertia::render('Dashboard', [
            'portal_mode' => null,
            'stats' => $stats,
        ]);
    }
}
