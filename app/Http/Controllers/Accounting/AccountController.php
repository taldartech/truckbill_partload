<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EntityAccountLinker;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounts', only: ['index', 'show']),
            new Middleware('permission:create accounts', only: ['store']),
            new Middleware('permission:edit accounts', only: ['update']),
            new Middleware('permission:delete accounts', only: ['destroy']),
        ];
    }

    public function index(Request $request, ChartOfAccountsService $chart)
    {
        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);
        if ($companyId > 0) {
            $chart->ensureDefaults($companyId);
        }

        $search = trim((string) $request->input('search', ''));

        $accounts = Account::query()
            ->with('accountGroup:id,name,nature')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Accounting/Accounts/Index', [
            'accounts' => $accounts,
            'groups' => AccountGroup::query()->active()->orderBy('name')->get(['id', 'name', 'nature']),
            'filters' => ['search' => $search],
        ]);
    }

    public function show(Account $account)
    {
        $account->load(['accountGroup', 'reference']);

        return Inertia::render('Accounting/Accounts/Show', [
            'account' => array_merge($account->toArray(), [
                'select_option' => $account->toSelectOption(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'account_group_id' => ['required', 'exists:account_groups,id'],
            'is_active' => ['boolean'],
            'is_selectable' => ['boolean'],
        ]);

        Account::query()->create([
            ...$validated,
            'is_system' => false,
            'is_active' => $validated['is_active'] ?? true,
            'is_selectable' => $validated['is_selectable'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Account created.');
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'account_group_id' => ['required', 'exists:account_groups,id'],
            'is_active' => ['boolean'],
            'is_selectable' => ['boolean'],
        ]);

        if ($account->is_system) {
            $account->update([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'] ?? $account->is_active,
                'is_selectable' => $validated['is_selectable'] ?? $account->is_selectable,
            ]);
        } else {
            $account->update($validated);
        }

        return redirect()->back()->with('success', 'Account updated.');
    }

    public function destroy(Account $account, EntityAccountLinker $linker)
    {
        if ($account->is_system) {
            return redirect()->back()->with('error', 'System accounts cannot be deleted.');
        }

        if (! $linker->deleteAccountIfSafe($account)) {
            return redirect()->back()->with('error', 'Account has posted history and cannot be deleted.');
        }

        return redirect()->route('accounting.accounts.index')->with('success', 'Account deleted.');
    }
}
