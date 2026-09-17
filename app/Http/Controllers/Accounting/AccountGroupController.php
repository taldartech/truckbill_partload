<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\AccountNature;
use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AccountGroupController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view account_groups', only: ['index']),
            new Middleware('permission:create account_groups', only: ['store']),
            new Middleware('permission:edit account_groups', only: ['update']),
            new Middleware('permission:delete account_groups', only: ['destroy']),
        ];
    }

    public function index(Request $request, ChartOfAccountsService $chart)
    {
        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);
        if ($companyId > 0) {
            $chart->ensureDefaults($companyId);
        }

        $groups = AccountGroup::query()
            ->with(['parent:id,name', 'children:id,parent_id,name'])
            ->withCount('accounts')
            ->orderBy('nature')
            ->orderBy('name')
            ->get();

        return Inertia::render('Accounting/AccountGroups/Index', [
            'groups' => $groups,
            'natures' => collect(AccountNature::cases())->map(fn (AccountNature $n) => [
                'value' => $n->value,
                'label' => $n->label(),
            ]),
            'parentOptions' => $groups->map(fn (AccountGroup $g) => [
                'id' => $g->id,
                'name' => $g->name.' ('.$g->nature->label().')',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nature' => ['required', Rule::enum(AccountNature::class)],
            'parent_id' => ['nullable', 'exists:account_groups,id'],
            'is_active' => ['boolean'],
        ]);

        AccountGroup::query()->create([
            ...$validated,
            'is_system' => false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Account group created.');
    }

    public function update(Request $request, AccountGroup $accountGroup)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nature' => ['required', Rule::enum(AccountNature::class)],
            'parent_id' => ['nullable', 'exists:account_groups,id', Rule::notIn([$accountGroup->id])],
            'is_active' => ['boolean'],
        ]);

        if ($accountGroup->is_system) {
            $accountGroup->update([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'] ?? $accountGroup->is_active,
            ]);
        } else {
            $accountGroup->update($validated);
        }

        return redirect()->back()->with('success', 'Account group updated.');
    }

    public function destroy(AccountGroup $accountGroup)
    {
        if ($accountGroup->is_system) {
            return redirect()->back()->with('error', 'System groups cannot be deleted.');
        }

        if ($accountGroup->accounts()->exists() || $accountGroup->children()->exists()) {
            return redirect()->back()->with('error', 'Remove child groups and accounts first.');
        }

        $accountGroup->delete();

        return redirect()->back()->with('success', 'Account group deleted.');
    }
}
