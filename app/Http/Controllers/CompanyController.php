<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class CompanyController extends Controller implements HasMiddleware
{
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
        $companies = Company::latest()->paginate(10);
        return Inertia::render('Companies/Index', [
            'companies' => $companies
        ]);
    }

    public function create()
    {
        return Inertia::render('Companies/Create', [
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'company_type' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:companies,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'pin_code' => 'nullable|string|max:10',
            'gst_number' => 'nullable|string|max:15',
            'gst_scheme' => 'nullable|string|max:50',
            'gst_type' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|max:20',
            'bank' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc' => 'nullable|string|max:20',
            'invoice_template' => 'nullable|string|max:50',
            'lr_copy_template' => 'nullable|string|max:50',
            'gst_slab' => 'nullable|numeric',
            'status' => 'boolean',
        ]);

        Company::create($validated);
        return redirect()->route('companies.index')->with('success', 'Company created successfully.');
    }

    public function edit(Company $company)
    {
        return Inertia::render('Companies/Edit', [
            'company' => $company,
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all()
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'company_type' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'pin_code' => 'nullable|string|max:10',
            'gst_number' => 'nullable|string|max:15',
            'gst_scheme' => 'nullable|string|max:50',
            'gst_type' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|max:20',
            'bank' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc' => 'nullable|string|max:20',
            'invoice_template' => 'nullable|string|max:50',
            'lr_copy_template' => 'nullable|string|max:50',
            'gst_slab' => 'nullable|numeric',
            'status' => 'boolean',
        ]);

        $company->update($validated);
        return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Company deleted successfully.');
    }
}
