<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\Reports\AccountLedgerReport;
use App\Services\Accounting\Reports\BalanceSheetReport;
use App\Services\Accounting\Reports\DayBookReport;
use App\Services\Accounting\Reports\OutstandingReport;
use App\Services\Accounting\Reports\ProfitAndLossReport;
use App\Services\Accounting\Reports\TrialBalanceReport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AccountingReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounting_reports'),
        ];
    }

    public function ledger(Request $request, AccountLedgerReport $report)
    {
        [$from, $to] = $this->dateRange($request);
        $accountId = (int) $request->input('account_id');
        $account = $accountId > 0 ? Account::query()->with('accountGroup')->find($accountId) : null;
        $ledger = $account ? $report->build($account, $from, $to) : null;

        return Inertia::render('Accounting/Reports/Ledger', [
            'accounts' => Account::query()->with('accountGroup:id,name')->orderBy('name')->get()->map->toSelectOption(),
            'account' => $account?->toSelectOption(),
            'ledger' => $ledger,
            'filters' => [
                'account_id' => $accountId ?: '',
                'start_date' => $from,
                'end_date' => $to,
            ],
        ]);
    }

    public function dayBook(Request $request, DayBookReport $report)
    {
        [$from, $to] = $this->dateRange($request);
        $companyId = (int) $this->tenant()->activeCompanyId();

        return Inertia::render('Accounting/Reports/DayBook', [
            'rows' => $report->build($companyId, $from, $to),
            'filters' => ['start_date' => $from, 'end_date' => $to],
        ]);
    }

    public function cashBook(Request $request, AccountLedgerReport $ledger, ChartOfAccountsService $chart)
    {
        return $this->moneyBook($request, $ledger, $chart, 'cash', 'Accounting/Reports/CashBook');
    }

    public function bankBook(Request $request, AccountLedgerReport $ledger)
    {
        [$from, $to] = $this->dateRange($request);
        $accountId = (int) $request->input('account_id');
        $account = $accountId > 0
            ? Account::query()->whereNotNull('bank_account_id')->with('accountGroup')->find($accountId)
            : null;

        return Inertia::render('Accounting/Reports/BankBook', [
            'accounts' => Account::query()->whereNotNull('bank_account_id')->orderBy('name')->get()->map->toSelectOption(),
            'account' => $account?->toSelectOption(),
            'ledger' => $account ? $ledger->build($account, $from, $to) : null,
            'filters' => [
                'account_id' => $accountId ?: '',
                'start_date' => $from,
                'end_date' => $to,
            ],
        ]);
    }

    public function trialBalance(Request $request, TrialBalanceReport $report)
    {
        [$from, $to] = $this->dateRange($request);
        $companyId = (int) $this->tenant()->activeCompanyId();

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'report' => $report->build($companyId, $from, $to),
            'filters' => ['start_date' => $from, 'end_date' => $to],
        ]);
    }

    public function profitAndLoss(Request $request, ProfitAndLossReport $report)
    {
        [$from, $to] = $this->dateRange($request);
        $companyId = (int) $this->tenant()->activeCompanyId();

        return Inertia::render('Accounting/Reports/ProfitAndLoss', [
            'report' => $report->build($companyId, $from, $to),
            'filters' => ['start_date' => $from, 'end_date' => $to],
        ]);
    }

    public function balanceSheet(Request $request, BalanceSheetReport $report, ProfitAndLossReport $pnl)
    {
        $asOn = $request->input('as_on', now()->toDateString());
        $companyId = (int) $this->tenant()->activeCompanyId();

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'report' => $report->build($companyId, $asOn, $pnl),
            'filters' => ['as_on' => $asOn],
        ]);
    }

    public function receivables(Request $request, OutstandingReport $report)
    {
        return $this->outstanding($request, $report, 'sundry_debtors', 'Accounting/Reports/Receivables');
    }

    public function payables(Request $request, OutstandingReport $report)
    {
        return $this->outstanding($request, $report, 'sundry_creditors', 'Accounting/Reports/Payables');
    }

    private function outstanding(Request $request, OutstandingReport $report, string $groupKey, string $page)
    {
        [$from, $to] = $this->dateRange($request);
        $companyId = (int) $this->tenant()->activeCompanyId();

        return Inertia::render($page, [
            'rows' => $report->build($companyId, $groupKey, $from, $to),
            'filters' => ['start_date' => $from, 'end_date' => $to],
        ]);
    }

    private function moneyBook(
        Request $request,
        AccountLedgerReport $ledger,
        ChartOfAccountsService $chart,
        string $systemKey,
        string $page,
    ) {
        [$from, $to] = $this->dateRange($request);
        $companyId = (int) $this->tenant()->activeCompanyId();
        $account = $companyId > 0 ? $chart->systemAccount($companyId, $systemKey) : null;

        return Inertia::render($page, [
            'account' => $account?->toSelectOption(),
            'ledger' => $account ? $ledger->build($account, $from, $to) : null,
            'filters' => ['start_date' => $from, 'end_date' => $to],
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dateRange(Request $request): array
    {
        $from = $request->input('start_date', now()->startOfMonth()->toDateString());
        $to = $request->input('end_date', now()->toDateString());

        return [
            Carbon::parse($from)->toDateString(),
            Carbon::parse($to)->toDateString(),
        ];
    }
}
