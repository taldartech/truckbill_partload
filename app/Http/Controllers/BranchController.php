<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Station;
use App\Services\SubscriptionGuard;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
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
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|regex:/^\d{9,18}$/',
            'ifsc_code' => 'nullable|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i',
            'contact_person' => 'required|string|max:100',
            'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'required|string|email|max:255',
            'bilty_initial' => 'nullable|string|max:20',
            'challan_initial' => 'nullable|string|max:20',
            'main_challan_initial' => 'nullable|string|max:20',
            'invoice_initial' => 'nullable|string|max:20',
            'inward_register_initial' => 'nullable|string|max:20',
            'use_company_logo' => 'boolean',
            'branch_logo' => 'nullable|image|max:2048',
            ...$this->biltyPrintConfigRules(),
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

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        $this->authorizeBranch($branch);

        return Inertia::render('Branches/Edit', [
            'branch' => tap($branch->load(['station', 'company:id,company_logo']), function (Branch $branch) {
                $branch->setAttribute('bilty_print_config', $branch->resolvedBiltyPrintConfig());
            }),
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
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|regex:/^\d{9,18}$/',
            'ifsc_code' => 'nullable|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i',
            'contact_person' => 'required|string|max:100',
            'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'required|string|email|max:255',
            'bilty_initial' => 'nullable|string|max:20',
            'challan_initial' => 'nullable|string|max:20',
            'main_challan_initial' => 'nullable|string|max:20',
            'invoice_initial' => 'nullable|string|max:20',
            'inward_register_initial' => 'nullable|string|max:20',
            'use_company_logo' => 'boolean',
            'branch_logo' => 'nullable|image|max:2048',
            ...$this->biltyPrintConfigRules(),
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

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorizeBranch($branch);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
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

        foreach (['bilty_initial', 'challan_initial', 'main_challan_initial', 'invoice_initial', 'inward_register_initial'] as $field) {
            $value = trim((string) ($validated[$field] ?? ''));
            $validated[$field] = $value !== '' ? $value : null;
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

        $validated['bilty_print_config'] = $this->normalizeBiltyPrintConfig($validated['bilty_print_config'] ?? null);

        return $validated;
    }

    private function biltyPrintConfigRules(): array
    {
        $rules = [
            'bilty_print_config' => 'nullable|array',
            'bilty_print_config.ask_amount_at_print' => 'boolean',
            'bilty_print_config.note' => 'nullable|string|max:1000',
        ];

        foreach (array_keys(Branch::defaultBiltyPrintConfig()['copies']) as $copy) {
            $rules["bilty_print_config.copies.{$copy}.enabled"] = 'boolean';
            $rules["bilty_print_config.copies.{$copy}.show_amount"] = 'boolean';
        }

        return $rules;
    }

    private function normalizeBiltyPrintConfig(?array $config): array
    {
        $defaults = Branch::defaultBiltyPrintConfig();
        $config = is_array($config) ? $config : [];

        $normalized = [
            'ask_amount_at_print' => (bool) ($config['ask_amount_at_print'] ?? $defaults['ask_amount_at_print']),
            'note' => $this->resolveBiltyPrintNoteFromPayload($config),
            'copies' => [],
        ];

        foreach ($defaults['copies'] as $key => $defaultCopy) {
            $storedCopy = is_array($config['copies'][$key] ?? null) ? $config['copies'][$key] : [];

            $normalized['copies'][$key] = [
                'enabled' => filter_var($storedCopy['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'show_amount' => filter_var($storedCopy['show_amount'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $enabledCount = collect($normalized['copies'])->filter(fn ($copy) => $copy['enabled'])->count();

        if ($enabledCount === 0) {
            abort(422, 'At least one bilty print copy must be enabled.');
        }

        return $normalized;
    }

    private function resolveBiltyPrintNoteFromPayload(array $config): string
    {
        $note = trim((string) ($config['note'] ?? ''));

        if ($note !== '') {
            return $note;
        }

        foreach ($config['copies'] ?? [] as $copy) {
            if (! is_array($copy)) {
                continue;
            }

            $legacyNote = trim((string) ($copy['note'] ?? ''));

            if ($legacyNote !== '') {
                return $legacyNote;
            }
        }

        return '';
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


    private function defaultCountryId(): ?int
    {
        return Country::query()->where('name', 'India')->value('id');
    }
}
