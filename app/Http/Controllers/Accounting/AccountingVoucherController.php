<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\AccountingVoucherType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingVoucher;
use App\Services\Accounting\AccountingAuditTrail;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountingVoucherController extends Controller implements HasMiddleware
{
    public const TEMPLATE_KEYS = [
        'expense', 'diesel', 'tds_receivable', 'tds_payable', 'credit_note',
        'debit_note', 'advance_paid', 'advance_received', 'contra',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounting_vouchers', only: ['index', 'show', 'create']),
            new Middleware('permission:create accounting_vouchers', only: ['store']),
            new Middleware('permission:edit accounting_vouchers', only: ['cancel', 'reverse']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'voucher_type' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $vouchers = AccountingVoucher::query()
            ->with(['entries.account:id,name'])
            ->when($validated['voucher_type'] ?? null, fn ($q, $type) => $q->where('voucher_type', $type))
            ->when($validated['search'] ?? null, function ($q) use ($validated) {
                $search = $validated['search'];
                $q->where(function ($inner) use ($search) {
                    $inner->where('voucher_number', 'like', '%'.$search.'%')
                        ->orWhere('narration', 'like', '%'.$search.'%');
                });
            })
            ->when($validated['start_date'] ?? null, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($validated['end_date'] ?? null, fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->latest('date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Accounting/Vouchers/Index', [
            'vouchers' => $vouchers,
            'filters' => $validated,
            'voucherTypes' => collect(AccountingVoucherType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
        ]);
    }

    public function create(Request $request, ChartOfAccountsService $chart)
    {
        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);
        if ($companyId > 0) {
            $chart->ensureDefaults($companyId);
        }

        $type = $request->input('type', AccountingVoucherType::Journal->value);

        return Inertia::render('Accounting/Vouchers/Create', [
            'voucherType' => $type,
            'voucherTypes' => collect(AccountingVoucherType::cases())
                ->reject(fn ($t) => $t === AccountingVoucherType::Opening)
                ->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])
                ->values(),
            'accounts' => Account::query()->selectable()->with('accountGroup:id,name,nature')->orderBy('name')->get()
                ->map(fn (Account $a) => $a->toSelectOption()),
            'defaults' => [
                'date' => now()->toDateString(),
                'cash_account_id' => $companyId > 0 ? $chart->systemAccount($companyId, 'cash')->id : null,
            ],
            'templates' => $companyId > 0 ? $this->templates($companyId, $chart) : [],
            'activeTemplate' => in_array($request->input('template'), self::TEMPLATE_KEYS, true) ? $request->input('template') : null,
        ]);
    }

    /**
     * Quick-entry templates: a voucher type plus default ledgers. A null account is chosen by the user.
     *
     * @return list<array{key: string, label: string, voucher_type: string, narration: string, lines: list<array{account_id: int|null, side: string, narration: string}>}>
     */
    private function templates(int $companyId, ChartOfAccountsService $chart): array
    {
        $account = fn (string $key): int => $chart->systemAccount($companyId, $key)->id;
        $line = fn (?int $accountId, string $side, string $narration = ''): array => [
            'account_id' => $accountId,
            'side' => $side,
            'narration' => $narration,
        ];

        return array_map(
            fn (array $template): array => [...$template, 'voucher_type' => $template['voucher_type']->value],
            [
                [
                    'key' => 'expense',
                    'label' => 'Expense',
                    'voucher_type' => AccountingVoucherType::Payment,
                    'narration' => 'Expense paid',
                    'lines' => [$line($account('general_expense'), 'debit'), $line($account('cash'), 'credit')],
                ],
                [
                    'key' => 'diesel',
                    'label' => 'Diesel',
                    'voucher_type' => AccountingVoucherType::Payment,
                    'narration' => 'Diesel purchase',
                    'lines' => [$line($account('diesel_expense'), 'debit'), $line($account('cash'), 'credit')],
                ],
                [
                    'key' => 'tds_receivable',
                    'label' => 'TDS Deducted by Party',
                    'voucher_type' => AccountingVoucherType::Journal,
                    'narration' => 'TDS deducted by party',
                    'lines' => [$line($account('tds_receivable'), 'debit'), $line(null, 'credit', 'Party')],
                ],
                [
                    'key' => 'tds_payable',
                    'label' => 'TDS Deducted on Payment',
                    'voucher_type' => AccountingVoucherType::Journal,
                    'narration' => 'TDS deducted on payment',
                    'lines' => [$line(null, 'debit', 'Party / Agent'), $line($account('tds_payable'), 'credit')],
                ],
                [
                    'key' => 'credit_note',
                    'label' => 'Credit Note',
                    'voucher_type' => AccountingVoucherType::CreditNote,
                    'narration' => 'Credit note issued',
                    'lines' => [$line($account('freight_income'), 'debit'), $line(null, 'credit', 'Party')],
                ],
                [
                    'key' => 'debit_note',
                    'label' => 'Debit Note',
                    'voucher_type' => AccountingVoucherType::DebitNote,
                    'narration' => 'Debit note issued',
                    'lines' => [$line(null, 'debit', 'Party'), $line($account('freight_income'), 'credit')],
                ],
                [
                    'key' => 'advance_paid',
                    'label' => 'Advance Paid',
                    'voucher_type' => AccountingVoucherType::Payment,
                    'narration' => 'Advance paid',
                    'lines' => [$line(null, 'debit', 'Party / Agent'), $line($account('cash'), 'credit')],
                ],
                [
                    'key' => 'advance_received',
                    'label' => 'Advance Received',
                    'voucher_type' => AccountingVoucherType::Receipt,
                    'narration' => 'Advance received',
                    'lines' => [$line($account('cash'), 'debit'), $line(null, 'credit', 'Party')],
                ],
                [
                    'key' => 'contra',
                    'label' => 'Contra (Cash / Bank)',
                    'voucher_type' => AccountingVoucherType::Contra,
                    'narration' => 'Cash deposited in bank',
                    'lines' => [$line(null, 'debit', 'Bank'), $line($account('cash'), 'credit')],
                ],
            ],
        );
    }

    public function store(Request $request, VoucherService $vouchers)
    {
        $companyId = (int) $this->tenant()->activeCompanyId();

        $validated = $request->validate([
            'voucher_type' => ['required', Rule::enum(AccountingVoucherType::class)],
            'date' => ['required', 'date'],
            'voucher_number' => ['nullable', 'string', 'max:50'],
            'narration' => ['nullable', 'string', 'max:2000'],
            'template' => ['nullable', 'string', Rule::in(self::TEMPLATE_KEYS)],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'integer', $this->existsInCompany('accounts')],
            'entries.*.debit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.credit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.narration' => ['nullable', 'string', 'max:500'],
        ]);

        $voucher = $vouchers->post([
            'company_id' => $companyId,
            'branch_id' => $this->tenant()->activeBranchId(),
            'voucher_type' => $validated['voucher_type'],
            'date' => $validated['date'],
            'voucher_number' => ($validated['voucher_number'] ?? null) ?: null,
            'narration' => $validated['narration'] ?? null,
            'event_key' => ! empty($validated['template']) ? 'manual.'.$validated['template'] : null,
        ], $validated['entries']);

        return redirect()
            ->route('accounting.vouchers.show', $voucher)
            ->with('success', 'Voucher posted.');
    }

    public function show(AccountingVoucher $voucher, AccountingAuditTrail $auditTrail)
    {
        $voucher->load([
            'entries.account.accountGroup',
            'reversedVoucher:id,voucher_number',
            'reversals:id,voucher_number,reversed_voucher_id,date',
            'createdBy:id,name',
            'updatedBy:id,name',
            'reversedBy:id,name',
            'cancelledBy:id,name',
        ]);

        return Inertia::render('Accounting/Vouchers/Show', [
            'voucher' => $voucher,
            'source' => $auditTrail->sourceReference($voucher),
            'eventLabel' => AccountingAuditTrail::eventLabel($voucher->event_key),
            'isSystemGenerated' => $voucher->isSystemGenerated(),
            'canReverse' => $voucher->isOpenForReversal(),
        ]);
    }

    public function cancel(Request $request, AccountingVoucher $voucher, VoucherService $vouchers)
    {
        $this->ensureManualVoucher($voucher);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $vouchers->cancel($voucher, null, $validated['reason'] ?? null);

        return redirect()->back()->with('success', 'Voucher cancelled.');
    }

    public function reverse(AccountingVoucher $voucher, VoucherService $vouchers)
    {
        $this->ensureManualVoucher($voucher);

        $reverse = $vouchers->reverse($voucher);

        return redirect()
            ->route('accounting.vouchers.show', $reverse)
            ->with('success', 'Reversal voucher posted.');
    }

    /**
     * System vouchers follow their source document; editing or deleting the document reverses them.
     *
     * @throws ValidationException
     */
    private function ensureManualVoucher(AccountingVoucher $voucher): void
    {
        if ($voucher->isSystemGenerated()) {
            throw ValidationException::withMessages([
                'voucher' => 'This voucher was posted automatically from a document. Edit or delete the source document instead.',
            ]);
        }
    }
}
