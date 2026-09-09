<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Party;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Services\Accounting\AccountingPoster;
use App\Services\Accounting\EntityBalance;
use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VoucherController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view vouchers', only: ['index', 'show', 'entityBalance']),
            new Middleware('permission:create vouchers', only: ['create', 'store']),
            new Middleware('permission:edit vouchers', only: ['edit', 'update']),
            new Middleware('permission:delete vouchers', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'voucher_type' => ['nullable', 'in:Payment,Receipt'],
        ]);

        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->endOfMonth()->toDateString();
        $search = trim((string) ($validated['search'] ?? ''));

        $vouchers = Voucher::query()
            ->with(['accountable', 'bankAccount'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('voucher_number', 'like', '%'.$search.'%')
                        ->orWhere('remark', 'like', '%'.$search.'%');
                });
            })
            ->when($fromDate, fn ($query) => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('date', '<=', $toDate))
            ->when(! empty($validated['voucher_type']), function ($query) use ($validated) {
                $query->where('voucher_type', $validated['voucher_type']);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'filters' => [
                'search' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'voucher_type' => $validated['voucher_type'] ?? '',
            ],
        ]);
    }

    public function create()
    {
        $branch = $this->activeBranch();
        $nextNumber = DocumentNumber::next(
            Voucher::query(),
            'voucher_number',
            $branch?->settings?->voucher_initial
        );

        return Inertia::render('Vouchers/Create', array_merge($this->formOptions(),['next_voucher_number' => $nextNumber]));
    }

    public function show(Voucher $voucher)
    {
        $voucher->load(['accountable', 'bankAccount']);

        return Inertia::render('Vouchers/Show', [
            'voucher' => [
                'id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'date' => $voucher->date,
                'voucher_type' => $voucher->voucher_type,
                'amount' => $voucher->amount,
                'mode' => $voucher->mode,
                'bank_account' => $voucher->bankAccount,
                'remark' => $voucher->remark,
                'entity_name' => $voucher->accountable?->name,
                'entity_type' => $this->entityKeyFromClass($voucher->accountable_type),
                'entity_type_label' => class_basename($voucher->accountable_type),
                'created_at' => $voucher->created_at,
                'updated_at' => $voucher->updated_at,
            ],
        ]);
    }

    public function store(Request $request, AccountingPoster $poster, EntityBalance $balances)
    {
        $validated = $this->validatedVoucher($request);
        $entity = $this->resolveEntity($validated['accountable_type'], (int) $validated['accountable_id']);
        $exceeds = $balances->exceedsOutstanding($entity, (float) $validated['amount'], $validated['voucher_type']);

        $voucher = DB::transaction(function () use ($validated, $entity, $poster) {
            $voucher = Voucher::create([
                'voucher_number' => $validated['voucher_number'],
                'date' => $validated['date'],
                'voucher_type' => $validated['voucher_type'],
                'accountable_type' => $entity::class,
                'accountable_id' => $entity->id,
                'amount' => $validated['amount'],
                'mode' => $validated['mode'],
                'bank_account_id' => $validated['mode'] === 'bank' ? $validated['bank_account_id'] : null,
                'remark' => $validated['remark'] ?? null,
            ]);

            $voucher->load(['accountable', 'bankAccount']);
            $poster->syncVoucher($voucher);

            return $voucher;
        });

        $redirect = redirect()->route('vouchers.index')->with('success', 'Voucher created successfully.');

        if ($exceeds) {
            $redirect->with('warning', 'Voucher amount exceeds the current outstanding balance. The transaction was saved anyway.');
        }

        return $redirect;
    }

    public function edit(Voucher $voucher)
    {
        $voucher->load(['accountable', 'bankAccount']);

        return Inertia::render('Vouchers/Edit', array_merge($this->formOptions(), [
            'voucher' => [
                'id' => $voucher->id,
                'date' => optional($voucher->date)?->toDateString(),
                'voucher_number' => $voucher->voucher_number,
                'voucher_type' => $voucher->voucher_type,
                'accountable_type' => $this->entityKeyFromClass($voucher->accountable_type),
                'accountable_id' => $voucher->accountable_id,
                'amount' => $voucher->amount,
                'mode' => $voucher->mode,
                'bank_account_id' => $voucher->bank_account_id,
                'remark' => $voucher->remark,
            ],
        ]));
    }

    public function update(Request $request, Voucher $voucher, AccountingPoster $poster, EntityBalance $balances)
    {
        $validated = $this->validatedVoucher($request);
        $entity = $this->resolveEntity($validated['accountable_type'], (int) $validated['accountable_id']);
        $exceeds = $balances->exceedsOutstanding($entity, (float) $validated['amount'], $validated['voucher_type']);

        DB::transaction(function () use ($validated, $entity, $voucher, $poster) {
            $voucher->update([
                'voucher_number' => $validated['voucher_number'],
                'date' => $validated['date'],
                'voucher_type' => $validated['voucher_type'],
                'accountable_type' => $entity::class,
                'accountable_id' => $entity->id,
                'amount' => $validated['amount'],
                'mode' => $validated['mode'],
                'bank_account_id' => $validated['mode'] === 'bank' ? $validated['bank_account_id'] : null,
                'remark' => $validated['remark'] ?? null,
            ]);

            $voucher->load(['accountable', 'bankAccount']);
            $poster->syncVoucher($voucher);
        });

        $redirect = redirect()->route('vouchers.index')->with('success', 'Voucher updated successfully.');

        if ($exceeds) {
            $redirect->with('warning', 'Voucher amount exceeds the current outstanding balance. The transaction was saved anyway.');
        }

        return $redirect;
    }

    public function destroy(Request $request, Voucher $voucher, AccountingPoster $poster)
    {
        DB::transaction(function () use ($request, $voucher, $poster) {
            $poster->clearReference($voucher);

            $voucher->update([
                'delete_remark' => $request->delete_remark,
                'deleted_by' => Auth::id(),
            ]);

            $voucher->delete();
        });

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher deleted successfully.');
    }

    public function entityBalance(Request $request, EntityBalance $balances)
    {
        $validated = $request->validate([
            'accountable_type' => ['required', 'in:vendor,agent,party'],
            'accountable_id' => ['required', 'integer'],
        ]);

        $entity = $this->resolveEntity($validated['accountable_type'], (int) $validated['accountable_id']);

        return response()->json([
            'outstanding' => $balances->outstanding($entity),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'parties' => Party::query()->orderBy('name')->get(['id', 'name']),
            'vendors' => Vendor::query()->orderBy('name')->get(['id', 'name']),
            'agents' => Agent::query()->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'bank_name', 'account_number']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedVoucher(Request $request): array
    {
        return $request->validate([
            'voucher_number' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'voucher_type' => ['required', 'in:Payment,Receipt'],
            'accountable_type' => ['required', 'in:vendor,agent,party'],
            'accountable_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'mode' => ['required', 'in:cash,bank'],
            'bank_account_id' => [
                Rule::requiredIf(fn () => $request->input('mode') === 'bank'),
                'nullable',
                'integer',
                'exists:bank_accounts,id',
            ],
            'remark' => ['nullable', 'string'],
        ]);
    }

    private function resolveEntity(string $type, int $id): Model
    {
        return match ($type) {
            'vendor' => Vendor::query()->findOrFail($id),
            'agent' => Agent::query()->findOrFail($id),
            'party' => Party::query()->findOrFail($id),
            default => throw new \InvalidArgumentException('Invalid accountable type.'),
        };
    }

    private function entityKeyFromClass(?string $class): ?string
    {
        return match ($class) {
            Vendor::class => 'vendor',
            Agent::class => 'agent',
            Party::class => 'party',
            default => null,
        };
    }

    protected function activeBranch(): ?Branch
    {
        return $this->tenant()->activeBranch();
    }
}
