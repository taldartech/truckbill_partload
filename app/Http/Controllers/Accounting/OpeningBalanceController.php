<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\AccountingVoucherType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\FinancialYearService;
use App\Services\Accounting\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class OpeningBalanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounting_settings', only: ['create']),
            new Middleware('permission:create accounting_settings', only: ['store']),
        ];
    }

    public function create(ChartOfAccountsService $chart, FinancialYearService $years)
    {
        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);
        $fy = null;
        if ($companyId > 0) {
            $chart->ensureDefaults($companyId);
            $fy = $years->ensureCurrent($companyId);
        }

        return Inertia::render('Accounting/OpeningBalances/Create', [
            'financialYear' => $fy,
            'accounts' => Account::query()
                ->selectable()
                ->with('accountGroup:id,name,nature')
                ->orderBy('name')
                ->get()
                ->map(fn (Account $a) => $a->toSelectOption()),
        ]);
    }

    public function store(Request $request, VoucherService $vouchers, FinancialYearService $years)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:2000'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'entries.*.debit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $companyId = (int) $this->tenant()->activeCompanyId();
        $fy = $years->forDate($companyId, $validated['date']);

        $voucher = $vouchers->post([
            'company_id' => $companyId,
            'branch_id' => $this->tenant()->activeBranchId(),
            'financial_year_id' => $fy->id,
            'voucher_type' => AccountingVoucherType::Opening,
            'date' => $validated['date'],
            'narration' => $validated['narration'] ?? 'Opening balances',
        ], $validated['entries']);

        return redirect()
            ->route('accounting.vouchers.show', $voucher)
            ->with('success', 'Opening balance voucher posted.');
    }
}
