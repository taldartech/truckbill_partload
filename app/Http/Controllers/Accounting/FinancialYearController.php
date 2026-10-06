<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Services\Accounting\FinancialYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class FinancialYearController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounting_settings', only: ['index']),
            new Middleware('permission:delete accounting_settings', only: ['close', 'reopen']),
        ];
    }

    public function index(FinancialYearService $years): Response
    {
        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);
        if ($companyId > 0) {
            $years->ensureCurrent($companyId);
        }

        return Inertia::render('Accounting/FinancialYears/Index', [
            'financialYears' => FinancialYear::query()
                ->with('closedBy:id,name')
                ->withCount('vouchers')
                ->orderByDesc('starts_on')
                ->get(),
        ]);
    }

    public function close(FinancialYear $financialYear, FinancialYearService $years): RedirectResponse
    {
        $years->close($financialYear);

        return redirect()->back()->with('success', "{$financialYear->name} closed. No entries can be posted or changed in it until it is reopened.");
    }

    public function reopen(FinancialYear $financialYear, FinancialYearService $years): RedirectResponse
    {
        $years->reopen($financialYear);

        return redirect()->back()->with('success', "{$financialYear->name} reopened.");
    }
}
