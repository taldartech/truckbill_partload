<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
        $branches = Branch::with('company')->latest()->paginate(10);
        return Inertia::render('Branches/Index', [
            'branches' => $branches
        ]);
    }

    public function create()
    {
        return Inertia::render('Branches/Create', [
            'companies' => Company::all(),
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all()
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
            'is_head_office' => 'boolean'
        ]);

        Branch::create($validated);
        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        return Inertia::render('Branches/Edit', [
            'branch' => $branch,
            'companies' => Company::all(),
            'countries' => Country::all(),
            'states' => State::all(),
            'cities' => City::all()
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string',
            'address' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'pin_code' => 'nullable|string|max:10',
            'is_head_office' => 'boolean'
        ]);

        $branch->update($validated);
        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }
}