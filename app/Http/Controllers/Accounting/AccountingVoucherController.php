<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\AccountingVoucherType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingVoucher;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AccountingVoucherController extends Controller implements HasMiddleware
{
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
        ]);
    }

    public function store(Request $request, VoucherService $vouchers)
    {
        $validated = $request->validate([
            'voucher_type' => ['required', Rule::enum(AccountingVoucherType::class)],
            'date' => ['required', 'date'],
            'voucher_number' => ['nullable', 'string', 'max:50'],
            'narration' => ['nullable', 'string', 'max:2000'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'entries.*.debit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.credit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.narration' => ['nullable', 'string', 'max:500'],
        ]);

        $companyId = (int) $this->tenant()->activeCompanyId();

        $voucher = $vouchers->post([
            'company_id' => $companyId,
            'branch_id' => $this->tenant()->activeBranchId(),
            'voucher_type' => $validated['voucher_type'],
            'date' => $validated['date'],
            'voucher_number' => $validated['voucher_number'] ?: null,
            'narration' => $validated['narration'] ?? null,
        ], $validated['entries']);

        return redirect()
            ->route('accounting.vouchers.show', $voucher)
            ->with('success', 'Voucher posted.');
    }

    public function show(AccountingVoucher $voucher)
    {
        $voucher->load(['entries.account.accountGroup', 'source', 'reversedVoucher', 'createdBy:id,name']);

        return Inertia::render('Accounting/Vouchers/Show', [
            'voucher' => $voucher,
        ]);
    }

    public function cancel(AccountingVoucher $voucher, VoucherService $vouchers)
    {
        $vouchers->cancel($voucher);

        return redirect()->back()->with('success', 'Voucher cancelled.');
    }

    public function reverse(AccountingVoucher $voucher, VoucherService $vouchers)
    {
        $reverse = $vouchers->reverse($voucher);

        return redirect()
            ->route('accounting.vouchers.show', $reverse)
            ->with('success', 'Reversal voucher posted.');
    }
}
