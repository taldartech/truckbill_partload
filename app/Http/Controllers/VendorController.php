<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\Vendor;
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
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'mobile' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
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
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'mobile' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
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
