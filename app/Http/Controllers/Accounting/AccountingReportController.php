<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\AccountingVoucherType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Agent;
use App\Models\Branch;
use App\Models\Party;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\Reports\AccountLedgerReport;
use App\Services\Accounting\Reports\BalanceSheetReport;
use App\Services\Accounting\Reports\DayBookReport;
use App\Services\Accounting\Reports\ExpenseReport;
use App\Services\Accounting\Reports\GstReport;
use App\Services\Accounting\Reports\OutstandingReport;
use App\Services\Accounting\Reports\ProfitAndLossReport;
use App\Services\Accounting\Reports\TdsReport;
use App\Services\Accounting\Reports\TrialBalanceReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
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
        return $this->ledgerPage($request, $report, Account::query(), 'Accounting/Reports/Ledger');
    }

    public function partyLedger(Request $request, AccountLedgerReport $report)
    {
        $partyAccounts = Account::query()->where(fn (Builder $query) => $query
            ->whereIn('reference_type', [Party::class, Agent::class])
            ->orWhereIn('system_key', ['sundry_debtors_unlinked', 'sundry_creditors_unlinked']));

        return $this->ledgerPage($request, $report, $partyAccounts, 'Accounting/Reports/PartyLedger');
    }

    public function dayBook(Request $request, DayBookReport $report)
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Accounting/Reports/DayBook', [
            'rows' => $report->build($this->reportCompanyId(), $from, $to, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    public function voucherRegister(Request $request, DayBookReport $report)
    {
        [$from, $to] = $this->dateRange($request);
        $validated = $request->validate([
            'voucher_type' => ['nullable', Rule::enum(AccountingVoucherType::class)],
        ]);
        $voucherType = $validated['voucher_type'] ?? null;

        return Inertia::render('Accounting/Reports/VoucherRegister', [
            'rows' => $report->build($this->reportCompanyId(), $from, $to, $this->reportBranchId($request), $voucherType),
            'voucherTypes' => collect(AccountingVoucherType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to, 'voucher_type' => $voucherType ?? '']),
            ...$this->branchOptions(),
        ]);
    }

    public function cashBook(Request $request, AccountLedgerReport $ledger, ChartOfAccountsService $chart)
    {
        [$from, $to] = $this->dateRange($request);
        $companyId = $this->reportCompanyId();
        $account = $companyId > 0 ? $chart->systemAccount($companyId, 'cash') : null;

        return Inertia::render('Accounting/Reports/CashBook', [
            'account' => $account?->toSelectOption(),
            'ledger' => $account ? $ledger->build($account, $from, $to, $this->reportBranchId($request)) : null,
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    public function bankBook(Request $request, AccountLedgerReport $ledger)
    {
        return $this->ledgerPage($request, $ledger, Account::query()->whereNotNull('bank_account_id'), 'Accounting/Reports/BankBook');
    }

    public function trialBalance(Request $request, TrialBalanceReport $report)
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'report' => $report->build($this->reportCompanyId(), $from, $to, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    public function profitAndLoss(Request $request, ProfitAndLossReport $report)
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Accounting/Reports/ProfitAndLoss', [
            'report' => $report->build($this->reportCompanyId(), $from, $to, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    public function balanceSheet(Request $request, BalanceSheetReport $report, ProfitAndLossReport $pnl)
    {
        $asOn = Carbon::parse($request->input('as_on', now()->toDateString()))->toDateString();

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'report' => $report->build($this->reportCompanyId(), $asOn, $pnl, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['as_on' => $asOn]),
            ...$this->branchOptions(),
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

    public function expenses(Request $request, ExpenseReport $report)
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Accounting/Reports/Expenses', [
            'report' => $report->build($this->reportCompanyId(), $from, $to, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    public function gst(Request $request, GstReport $report)
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Accounting/Reports/Gst', [
            'report' => $report->build($this->reportCompanyId(), $from, $to, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    public function tds(Request $request, TdsReport $report)
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Accounting/Reports/Tds', [
            'report' => $report->build($this->reportCompanyId(), $from, $to, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    private function outstanding(Request $request, OutstandingReport $report, string $groupKey, string $page)
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render($page, [
            'rows' => $report->build($this->reportCompanyId(), $groupKey, $from, $to, $this->reportBranchId($request)),
            'filters' => $this->filters($request, ['start_date' => $from, 'end_date' => $to]),
            ...$this->branchOptions(),
        ]);
    }

    /**
     * @param  Builder<Account>  $accounts
     */
    private function ledgerPage(Request $request, AccountLedgerReport $report, Builder $accounts, string $page)
    {
        [$from, $to] = $this->dateRange($request);
        $accountId = (int) $request->input('account_id');
        $account = $accountId > 0 ? (clone $accounts)->with('accountGroup')->find($accountId) : null;

        return Inertia::render($page, [
            'accounts' => $accounts->with('accountGroup:id,name')->orderBy('name')->get()->map->toSelectOption(),
            'account' => $account?->toSelectOption(),
            'ledger' => $account ? $report->build($account, $from, $to, $this->reportBranchId($request)) : null,
            'filters' => $this->filters($request, [
                'account_id' => $accountId ?: '',
                'start_date' => $from,
                'end_date' => $to,
            ]),
            ...$this->branchOptions(),
        ]);
    }

    private function reportCompanyId(): int
    {
        return (int) ($this->tenant()->activeCompanyId() ?? 0);
    }

    /**
     * Company admins may pick any branch or the whole company; everyone else sees only their active branch.
     */
    private function reportBranchId(Request $request): ?int
    {
        if (! $this->canViewAllBranches()) {
            return $this->tenant()->activeBranchId();
        }

        $branchId = (int) $request->input('branch_id');
        if ($branchId <= 0) {
            return null;
        }

        return Branch::query()->withoutGlobalScopes()->where('company_id', $this->reportCompanyId())->whereKey($branchId)->exists()
            ? $branchId
            : null;
    }

    private function canViewAllBranches(): bool
    {
        return $this->tenant()->isPlatformAdmin()
            || (bool) $this->tenant()->user()?->hasRole(UserRole::Admin->value);
    }

    /**
     * @return array{branches: list<array{id: int, name: string}>|null}
     */
    private function branchOptions(): array
    {
        if (! $this->canViewAllBranches()) {
            return ['branches' => null];
        }

        return [
            'branches' => Branch::query()
                ->withoutGlobalScopes()
                ->where('company_id', $this->reportCompanyId())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Branch $branch) => ['id' => $branch->id, 'name' => $branch->name])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filters(Request $request, array $filters): array
    {
        return [...$filters, 'branch_id' => $this->canViewAllBranches() ? ($this->reportBranchId($request) ?? '') : ''];
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
