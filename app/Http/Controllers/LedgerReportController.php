<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\Vendor;
use App\Services\Accounting\LedgerAccountResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LedgerReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view reports'),
        ];
    }

    public function partyLedger(Request $request, LedgerAccountResolver $resolver)
    {
        return $this->entityLedger($request, $resolver, 'party', Party::class, 'Reports/PartyLedger', 'parties');
    }

    public function vendorLedger(Request $request, LedgerAccountResolver $resolver)
    {
        return $this->entityLedger($request, $resolver, 'vendor', Vendor::class, 'Reports/VendorLedger', 'vendors');
    }

    public function agentLedger(Request $request, LedgerAccountResolver $resolver)
    {
        return $this->entityLedger($request, $resolver, 'agent', Agent::class, 'Reports/AgentLedger', 'agents');
    }

    public function cashBankBook(Request $request, LedgerAccountResolver $resolver)
    {
        [$startDate, $endDate] = $this->validatedDateRange($request);

        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);
        $bankAccounts = BankAccount::query()->orderBy('name')->get(['id', 'name', 'bank_name']);

        $options = collect([
            ['id' => 'cash', 'name' => 'Cash'],
        ])->concat($bankAccounts->map(fn (BankAccount $bank) => [
            'id' => 'bank:'.$bank->id,
            'name' => $bank->bank_name ? "{$bank->name} ({$bank->bank_name})" : $bank->name,
        ]))->values();

        $accountKey = $request->input('account');
        $ledgerAccount = null;
        $selectedLabel = null;

        if ($accountKey === 'cash' && $companyId > 0) {
            $ledgerAccount = $resolver->cash($companyId);
            $selectedLabel = 'Cash';
        } elseif (is_string($accountKey) && str_starts_with($accountKey, 'bank:')) {
            $bankId = (int) substr($accountKey, 5);
            $bank = BankAccount::query()->find($bankId);
            if ($bank) {
                $ledgerAccount = $resolver->forBankAccount($bank);
                $selectedLabel = $bank->name;
            }
        }

        $ledger = [];
        if ($ledgerAccount) {
            $ledger = $this->buildLedgerRows($ledgerAccount, $startDate, $endDate);
        }

        return Inertia::render('Reports/CashBankBook', [
            'accounts' => $options,
            'account' => $selectedLabel,
            'ledger' => $ledger,
            'filters' => [
                'account' => $accountKey ?? '',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function entityLedger(
        Request $request,
        LedgerAccountResolver $resolver,
        string $param,
        string $modelClass,
        string $component,
        string $collectionKey,
    ) {
        [$startDate, $endDate] = $this->validatedDateRange($request);

        $validated = $request->validate([
            $param.'_id' => ['nullable', 'integer'],
        ]);

        $entityId = $validated[$param.'_id'] ?? null;
        $entity = $entityId ? $modelClass::query()->find($entityId) : null;
        $ledger = [];

        if ($entity) {
            $account = $resolver->forEntity($entity);
            $ledger = $this->buildLedgerRows($account, $startDate, $endDate);
        }

        return Inertia::render($component, [
            $collectionKey => $modelClass::query()->orderBy('name')->get(['id', 'name']),
            $param => $entity,
            'ledger' => $ledger,
            'filters' => [
                $param.'_id' => $entityId ? (string) $entityId : '',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function validatedDateRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->endOfMonth()->toDateString();

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->gt($start->copy()->addMonthNoOverflow())) {
            throw ValidationException::withMessages([
                'end_date' => 'Date range cannot exceed 1 month.',
            ]);
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * @return list<array{date: mixed, type: string, ref_no: ?string, description: ?string, debit: float, credit: float, balance: float}>
     */
    private function buildLedgerRows(LedgerAccount $account, string $startDate, string $endDate): array
    {
        $opening = LedgerEntry::query()
            ->where('ledger_account_id', $account->id)
            ->whereDate('date', '<', $startDate)
            ->selectRaw('COALESCE(SUM(debit),0) as debit_total, COALESCE(SUM(credit),0) as credit_total')
            ->first();

        $openingDebit = (float) ($opening->debit_total ?? 0);
        $openingCredit = (float) ($opening->credit_total ?? 0);
        $running = round($openingDebit - $openingCredit, 2);
        $rows = [];

        if ($openingDebit != 0.0 || $openingCredit != 0.0) {
            $rows[] = [
                'date' => $startDate,
                'type' => 'Opening',
                'ref_no' => null,
                'description' => 'Opening balance',
                'debit' => $openingDebit > $openingCredit ? round($openingDebit - $openingCredit, 2) : 0.0,
                'credit' => $openingCredit > $openingDebit ? round($openingCredit - $openingDebit, 2) : 0.0,
                'balance' => $running,
            ];
        }

        $entries = LedgerEntry::query()
            ->where('ledger_account_id', $account->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $debit = (float) $entry->debit;
            $credit = (float) $entry->credit;
            $running = round($running + $debit - $credit, 2);

            $rows[] = [
                'date' => optional($entry->date)?->toDateString() ?? $entry->date,
                'type' => $entry->voucher_type,
                'ref_no' => $entry->voucher_number,
                'description' => $entry->remarks,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $running,
            ];
        }

        // Latest entry first (running balance still computed chronologically).
        return array_reverse($rows);
    }
}
