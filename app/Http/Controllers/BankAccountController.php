<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Services\Accounting\LedgerAccountResolver;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BankAccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view bank_accounts', only: ['index', 'show']),
            new Middleware('permission:create bank_accounts', only: ['create', 'store']),
            new Middleware('permission:edit bank_accounts', only: ['edit', 'update']),
            new Middleware('permission:delete bank_accounts', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $bankAccounts = BankAccount::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('bank_name', 'like', '%'.$search.'%')
                        ->orWhere('account_number', 'like', '%'.$search.'%')
                        ->orWhere('ifsc_code', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('BankAccounts/Index', [
            'bankAccounts' => $bankAccounts,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return redirect()->route('bank-accounts.index');
    }

    public function store(Request $request, LedgerAccountResolver $resolver)
    {
        $validated = $this->validatedPayload($request);

        DB::transaction(function () use ($validated, $resolver) {
            $bankAccount = BankAccount::create($validated);
            $resolver->forBankAccount($bankAccount);
        });

        return redirect()->back()->with('success', 'Bank account created successfully.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return redirect()->route('bank-accounts.index');
    }

    public function update(Request $request, BankAccount $bankAccount, LedgerAccountResolver $resolver)
    {
        $validated = $this->validatedPayload($request);

        DB::transaction(function () use ($validated, $bankAccount, $resolver) {
            $bankAccount->update($validated);
            $ledger = $resolver->forBankAccount($bankAccount->fresh());
            $ledger->update(['name' => $bankAccount->name]);
        });

        return redirect()->back()->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->back()->with('success', 'Bank account deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        unset($validated['company_id'], $validated['branch_id']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        return $validated;
    }
}
