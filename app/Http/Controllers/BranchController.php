<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\Scopes\CompanyScope;
use App\Models\State;
use App\Models\Station;
use App\Services\SubscriptionGuard;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BranchController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SubscriptionGuard $subscriptionGuard,
    ) {}

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
            'active_branch_id' => $context->sessionBranchId(),
            'can_select_branch' => $context->isSuperAdmin(),
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
        $validated = $request->validate([
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
            'pin_code' => 'required|string|digits:6',
            'station_id' => 'required|exists:stations,id',
            'is_head_office' => 'boolean',
            'has_gst' => 'boolean',
            'gst_number' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'gst_type' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|string|max:50',
            'gst_scheme' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|string|max:50',
            'gst_slab' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|numeric|min:0|max:100',
            'gst_exemption_limit' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|regex:/^\d{9,18}$/',
            'ifsc_code' => 'nullable|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i',
            'contact_person' => 'required|string|max:100',
            'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'required|string|email|max:255',
            'use_company_logo' => 'boolean',
            'branch_logo' => 'nullable|image|max:2048',
        ], [
            'contact_number.digits' => 'Contact number must be exactly 10 digits.',
            'contact_number.regex' => 'Contact number must be a valid 10-digit Indian mobile number.',
            'pin_code.digits' => 'PIN code must be exactly 6 digits.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
            'ifsc_code.size' => 'IFSC code must be exactly 11 characters.',
            'ifsc_code.regex' => 'IFSC code format is invalid.',
            'account_number.regex' => 'Account number must be between 9 and 18 digits.',
        ]);

        $companyId = $validated['company_id'] ?? app(TenantContext::class)->activeCompanyId();
        $this->authorizeCompanyId($companyId);
        if ($companyId) {
            $company = Company::query()->with('subscription')->findOrFail($companyId);
            $this->subscriptionGuard->assertCanCreateBranch($company);
        }
        $this->validateStationForOwner($validated['station_id']);

        $validated['company_id'] = $companyId;
        $validated = $this->normalizeBranchPayload($request, $validated);
        $this->ensureUniqueHeadOffice($validated);

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        $this->authorizeBranch($branch);

        $branch->load(['station', 'city', 'state', 'company', 'settings']);

        return Inertia::render('Branches/Show', [
            'branch' => $branch,
        ]);
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

        $validated = $request->validate([
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
            'pin_code' => 'required|string|digits:6',
            'station_id' => 'required|exists:stations,id',
            'is_head_office' => 'boolean',
            'has_gst' => 'boolean',
            'gst_number' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'gst_type' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|string|max:50',
            'gst_scheme' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|string|max:50',
            'gst_slab' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|numeric|min:0|max:100',
            'gst_exemption_limit' => 'nullable|required_if:has_gst,1|required_if:has_gst,true|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|regex:/^\d{9,18}$/',
            'ifsc_code' => 'nullable|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i',
            'contact_person' => 'required|string|max:100',
            'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'required|string|email|max:255',
            'use_company_logo' => 'boolean',
            'branch_logo' => 'nullable|image|max:2048',
        ], [
            'contact_number.digits' => 'Contact number must be exactly 10 digits.',
            'contact_number.regex' => 'Contact number must be a valid 10-digit Indian mobile number.',
            'pin_code.digits' => 'PIN code must be exactly 6 digits.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
            'ifsc_code.size' => 'IFSC code must be exactly 11 characters.',
            'ifsc_code.regex' => 'IFSC code format is invalid.',
            'account_number.regex' => 'Account number must be between 9 and 18 digits.',
        ]);

        if (! empty($validated['company_id'])) {
            $this->authorizeCompanyId($validated['company_id']);
        }

        $this->validateStationForOwner($validated['station_id']);

        $validated = $this->normalizeBranchPayload($request, $validated, $branch);
        $this->ensureUniqueHeadOffice($validated, $branch);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Request $request, Branch $branch)
    {
        $this->authorizeBranch($branch);
        $branch->update([
            'delete_remark' => $request->delete_remark,
            'deleted_by' => Auth::id(),
        ]);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }

    private function normalizeBranchPayload(Request $request, array $validated, ?Branch $branch = null): array
    {
        $validated['is_head_office'] = (bool) ($validated['is_head_office'] ?? false);
        $validated['has_gst'] = (bool) ($validated['has_gst'] ?? false);
        $validated['use_company_logo'] = (bool) ($validated['use_company_logo'] ?? true);

        if (! $validated['has_gst']) {
            $validated['gst_number'] = null;
            $validated['gst_type'] = null;
            $validated['gst_scheme'] = null;
            $validated['gst_slab'] = 0;
            $validated['gst_exemption_limit'] = null;
        }

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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureUniqueHeadOffice(array $validated, ?Branch $branch = null): void
    {
        if (! ($validated['is_head_office'] ?? false)) {
            return;
        }

        $companyId = $validated['company_id'] ?? $branch?->company_id;

        if (! $companyId) {
            return;
        }

        $alreadyHasHeadOffice = Branch::query()
            ->withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('is_head_office', true)
            ->when($branch, fn ($query) => $query->whereKeyNot($branch->id))
            ->exists();

        if ($alreadyHasHeadOffice) {
            throw ValidationException::withMessages([
                'is_head_office' => 'This company already has a head office branch.',
            ]);
        }
    }

    private function availableCompanies()
    {
        $context = app(TenantContext::class);
        $columns = [
            'companies.id',
            'companies.name',
            'companies.address',
            'companies.country_id',
            'companies.state_id',
            'companies.city_id',
            'companies.pin_code',
            'companies.company_logo',
            'companies.has_gst',
            'companies.gst_number',
            'companies.gst_type',
            'companies.gst_scheme',
            'companies.gst_slab',
            'companies.gst_exemption_limit',
            'companies.bank_name',
            'companies.account_number',
            'companies.ifsc_code',
        ];

        $query = Company::query()
            ->select($columns)
            ->addSelect([
                'head_office_branch_id' => Branch::query()
                    ->withoutGlobalScope(CompanyScope::class)
                    ->select('id')
                    ->whereColumn('branches.company_id', 'companies.id')
                    ->where('is_head_office', true)
                    ->limit(1),
            ])
            ->orderBy('companies.name');

        if ($context->isMasterAdmin()) {
            return $query->get();
        }

        if ($context->isSuperAdmin()) {
            return $query->where('companies.user_id', auth()->id())->get();
        }

        $companyId = $context->activeCompanyId();

        if (! $companyId) {
            return collect();
        }

        return $query->whereKey($companyId)->get();
    }

    private function availableStations()
    {
        return Station::query()
            ->orderBy('name')
            ->get(['id', 'name', 'country_id', 'state_id', 'city_id']);
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

    private function defaultCountryId(): ?int
    {
        return Country::query()->where('name', 'India')->value('id');
    }
}
