<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\AccountingIntegrity;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AccountingHealthController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounting_settings'),
        ];
    }

    public function index(AccountingIntegrity $integrity)
    {
        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);

        return Inertia::render('Accounting/Health/Index', [
            'report' => $companyId > 0 ? $integrity->audit($companyId) : null,
        ]);
    }
}
