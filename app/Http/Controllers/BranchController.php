<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BranchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view branches', only: ['index', 'show']),
            new Middleware('permission:create branches', only: ['create', 'store']),
            new Middleware('permission:edit branches', only: ['edit', 'update']),
            new Middleware('permission:delete branches', only: ['destroy']),
        ];
    }

    public function index()
    {
        $context = app(TenantContext::class);
        $query = Branch::with('company')->latest();

        if ($context->isSuperAdmin()) {
            $query->whereIn('company_id', $context->ownedCompanyIds());
        } elseif ($context->activeCompanyId()) {
            $query->where('company_id', $context->activeCompanyId());
        }

        return Inertia::render('Branches/Index', [
            'branches' => $query->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('Branches/Create', [
            'companies' => $this->availableCompanies(),
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string',
            'address' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'pin_code' => 'nullable|string|max:10',
            'is_head_office' => 'boolean',
        ]);

        $companyId = $validated['company_id'] ?? app(TenantContext::class)->activeCompanyId();
        $this->authorizeCompanyId($companyId);
        $this->enforceBranchLimit($companyId);

        $validated['company_id'] = $companyId;
        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        $this->authorizeBranch($branch);

        return Inertia::render('Branches/Edit', [
            'branch' => $branch,
            'companies' => $this->availableCompanies(),
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all(),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeBranch($branch);

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string',
            'address' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'pin_code' => 'nullable|string|max:10',
            'is_head_office' => 'boolean',
        ]);

        if (! empty($validated['company_id'])) {
            $this->authorizeCompanyId($validated['company_id']);
        }

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorizeBranch($branch);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }

    private function availableCompanies()
    {
        $context = app(TenantContext::class);

        if ($context->isMasterAdmin()) {
            return Company::orderBy('name')->get(['id', 'name']);
        }

        return Company::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeCompanyId(?int $companyId): void
    {
        if (! $companyId) {
            abort(422, 'Company is required.');
        }

        if (auth()->user()?->isSuperAdmin()) {
            $company = Company::find($companyId);

            if (! $company || (int) $company->user_id !== (int) auth()->id()) {
                abort(403);
            }
        }
    }

    private function authorizeBranch(Branch $branch): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            $company = Company::find($branch->company_id);

            if (! $company || (int) $company->user_id !== (int) auth()->id()) {
                abort(403);
            }
        }
    }

    private function enforceBranchLimit(?int $companyId): void
    {
        if (! $companyId) {
            return;
        }

        $subscription = DB::table('subscriptions')
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.company_id', $companyId)
            ->where('subscriptions.status', 'active')
            ->orderByDesc('subscriptions.ends_at')
            ->select('subscription_plans.max_branches')
            ->first();

        if (! $subscription || ! $subscription->max_branches) {
            return;
        }

        $currentCount = Branch::where('company_id', $companyId)->count();

        if ($currentCount >= $subscription->max_branches) {
            abort(422, 'Branch limit reached for the active subscription plan.');
        }
    }
}
