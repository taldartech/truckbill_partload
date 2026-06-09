<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class VendorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view vendors', only: ['index', 'show']),
            new Middleware('permission:create vendors', only: ['create', 'store']),
            new Middleware('permission:edit vendors', only: ['edit', 'update']),
            new Middleware('permission:delete vendors', only: ['destroy']),
        ];
    }

    public function index()
    {
        $vendors = Vendor::with('company')->latest()->paginate(10);

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
        ]);
    }

    public function create()
    {
        return Inertia::render('Vendors/Create', [
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gst_number' => 'nullable|string|max:50',
        ]));

        Vendor::create($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor)
    {
        return Inertia::render('Vendors/Edit', [
            'vendor' => $vendor,
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gst_number' => 'nullable|string|max:50',
        ]));

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }
}
