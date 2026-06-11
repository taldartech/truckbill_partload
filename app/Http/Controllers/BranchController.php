<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Station;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        $query = Branch::with(['company', 'station', 'city'])->latest();

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
            'stations' => $this->availableStations(),
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'defaultCountryId' => $this->defaultCountryId(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateBranch($request);

        $companyId = $validated['company_id'] ?? app(TenantContext::class)->activeCompanyId();
        $this->authorizeCompanyId($companyId);
        $this->enforceBranchLimit($companyId);
        $this->validateStationForOwner($validated['station_id']);

        $validated['company_id'] = $companyId;
        $validated = $this->normalizeBranchPayload($request, $validated);

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        $this->authorizeBranch($branch);

        return Inertia::render('Branches/Edit', [
            'branch' => $branch->load(['station', 'company:id,company_logo']),
            'companies' => $this->availableCompanies(),
            'stations' => $this->availableStations(),
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'defaultCountryId' => $this->defaultCountryId(),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeBranch($branch);

        $validated = $this->validateBranch($request);

        if (! empty($validated['company_id'])) {
            $this->authorizeCompanyId($validated['company_id']);
        }

        $this->validateStationForOwner($validated['station_id']);

        $validated = $this->normalizeBranchPayload($request, $validated, $branch);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorizeBranch($branch);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }

    private function validateBranch(Request $request): array
    {
        return $request->validate([
            'company_id' => [
                'nullable',
                'exists:companies,id',
                Rule::requiredIf(function () {
                    $context = app(TenantContext::class);

                    return auth()->user()?->isSuperAdmin() && ! $context->activeCompanyId();
                }),
            ],
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'pin_code' => 'required|string|max:10',
            'station_id' => 'required|exists:stations,id',
            'is_head_office' => 'boolean',
            'has_gst' => 'boolean',
            'gst_number' => 'nullable|required_if:has_gst,true|string|max:15',
            'gst_type' => 'nullable|required_if:has_gst,true|string|max:50',
            'gst_scheme' => 'nullable|required_if:has_gst,true|string|max:50',
            'gst_slab' => 'nullable|required_if:has_gst,true|numeric',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'contact_person' => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'bilty_initial' => 'nullable|string|max:20',
            'challan_initial' => 'nullable|string|max:20',
            'main_challan_initial' => 'nullable|string|max:20',
            'invoice_initial' => 'nullable|string|max:20',
            'inward_register_initial' => 'nullable|string|max:20',
            'use_company_logo' => 'boolean',
            'branch_logo' => 'nullable|image|max:2048',
        ]);
    }

    private function normalizeBranchPayload(Request $request, array $validated, ?Branch $branch = null): array
    {
        $validated['has_gst'] = (bool) ($validated['has_gst'] ?? false);
        $validated['use_company_logo'] = (bool) ($validated['use_company_logo'] ?? true);

        if (! $validated['has_gst']) {
            $validated['gst_number'] = null;
            $validated['gst_type'] = null;
            $validated['gst_scheme'] = null;
            $validated['gst_slab'] = 0;
        }

        $validated['bilty_initial'] = $validated['bilty_initial'] ?: 'BLT-';
        $validated['challan_initial'] = $validated['challan_initial'] ?: 'CHL-';
        $validated['main_challan_initial'] = $validated['main_challan_initial'] ?: 'MCH-';
        $validated['invoice_initial'] = $validated['invoice_initial'] ?: 'INV-';
        $validated['inward_register_initial'] = $validated['inward_register_initial'] ?: 'IR-';

        if ($request->hasFile('branch_logo')) {
            $path = $request->file('branch_logo')->store('branch_logos', 'public');
            $validated['branch_logo'] = '/storage/'.$path;
            $validated['use_company_logo'] = false;
        } elseif ($validated['use_company_logo']) {
            $validated['branch_logo'] = null;
        } else {
            unset($validated['branch_logo']);
        }

        return $validated;
    }

    private function availableCompanies()
    {
        $context = app(TenantContext::class);

        if ($context->isMasterAdmin()) {
            return Company::orderBy('name')->get(['id', 'name']);
        }

        return Company::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
    }

    private function availableStations()
    {
        return Station::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function validateStationForOwner(int $stationId): void
    {
        $station = Station::query()
            ->whereKey($stationId)
            ->exists();

        if (! $station) {
            abort(422, 'Selected station is not available for this account.');
        }
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

    private function defaultCountryId(): ?int
    {
        return Country::query()->where('name', 'India')->value('id');
    }
}
