<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Services\SubscriptionPricingService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class CompanyController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SubscriptionPricingService $pricingService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view companies', only: ['index', 'show']),
            new Middleware('permission:create companies', only: ['create', 'store']),
            new Middleware('permission:edit companies', only: ['edit', 'update']),
            new Middleware('permission:delete companies', only: ['destroy']),
        ];
    }

    public function index()
    {
        $context = app(TenantContext::class);
        $query = Company::query()->with(['city', 'state'])->latest();

        if ($context->isSuperAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return Inertia::render('Companies/Index', [
            'companies' => $query->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('Companies/Create', [
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'defaultCountryId' => $this->defaultCountryId(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:companies,email',
            'address' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'pin_code' => 'required|string|digits:6',
            'contact_person' => 'required|string|max:100',
            'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'pan_no' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
            'company_logo' => 'nullable|image|max:2048',
        ], [
            'contact_number.digits' => 'Contact number must be exactly 10 digits.',
            'contact_number.regex' => 'Contact number must be a valid 10-digit Indian mobile number.',
            'pin_code.digits' => 'PIN code must be exactly 6 digits.',
            'pan_no.size' => 'PAN must be exactly 10 characters.',
            'pan_no.regex' => 'PAN format is invalid.',
        ]);

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('company_logos', 'public');
            $validated['company_logo'] = '/storage/'.$path;
        } else {
            unset($validated['company_logo']);
        }

        if (auth()->user()?->isSuperAdmin()) {
            $validated['user_id'] = auth()->id();
        }

        $company = Company::create($validated);
        $this->pricingService->createTrialSubscription($company);

        return redirect()->route('companies.index')->with('success', 'Company created successfully.');
    }

    public function edit(Company $company)
    {
        $this->authorizeCompany($company);

        return Inertia::render('Companies/Edit', [
            'company' => $company,
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'defaultCountryId' => $this->defaultCountryId(),
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:companies,email,'.$company->id,
            'address' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'pin_code' => 'required|string|digits:6',
            'contact_person' => 'required|string|max:100',
            'contact_number' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'pan_no' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
            'company_logo' => 'nullable|image|max:2048',
        ], [
            'contact_number.digits' => 'Contact number must be exactly 10 digits.',
            'contact_number.regex' => 'Contact number must be a valid 10-digit Indian mobile number.',
            'pin_code.digits' => 'PIN code must be exactly 6 digits.',
            'pan_no.size' => 'PAN must be exactly 10 characters.',
            'pan_no.regex' => 'PAN format is invalid.',
        ]);

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('company_logos', 'public');
            $validated['company_logo'] = '/storage/'.$path;
        } else {
            unset($validated['company_logo']);
        }

        $company->update($validated);

        return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        $this->authorizeCompany($company);
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Company deleted successfully.');
    }

    private function authorizeCompany(Company $company): void
    {
        if (auth()->user()?->isSuperAdmin() && (int) $company->user_id !== (int) auth()->id()) {
            abort(403);
        }
    }

    private function defaultCountryId(): ?int
    {
        return Country::query()->where('name', 'India')->value('id');
    }
}
