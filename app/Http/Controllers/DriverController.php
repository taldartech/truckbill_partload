<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Driver;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class DriverController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view drivers', only: ['index', 'show']),
            new Middleware('permission:create drivers', only: ['create', 'store']),
            new Middleware('permission:edit drivers', only: ['edit', 'update']),
            new Middleware('permission:delete drivers', only: ['destroy']),
        ];
    }

    public function index()
    {
        $drivers = Driver::with('company')->orderBy('name', 'asc')->paginate(10);

        return Inertia::render('Drivers/Index', [
            'drivers' => $drivers,
        ]);
    }

    public function create()
    {
        return Inertia::render('Drivers/Create', [
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'license_number' => 'nullable|string|max:20',
            'license_expiry_date' => 'nullable|date',
            'aadhar_no' => 'nullable|string|digits:12',
            'pan_no' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
            'rto_name' => 'nullable|string|max:255',
            'rto_state' => 'nullable|string|max:255',
            'license_verification' => 'nullable|string|max:255',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'aadhar_no.digits' => 'Aadhaar number must be exactly 12 digits.',
            'pan_no.size' => 'PAN must be exactly 10 characters.',
            'pan_no.regex' => 'PAN format is invalid.',
        ]));

        Driver::create($validated);

        return redirect()->route('drivers.index')->with('success', 'Driver created successfully.');
    }

    public function edit(Driver $driver)
    {
        return Inertia::render('Drivers/Edit', [
            'driver' => $driver,
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'license_number' => 'nullable|string|max:20',
            'license_expiry_date' => 'nullable|date',
            'aadhar_no' => 'nullable|string|digits:12',
            'pan_no' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
            'rto_name' => 'nullable|string|max:255',
            'rto_state' => 'nullable|string|max:255',
            'license_verification' => 'nullable|string|max:255',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'aadhar_no.digits' => 'Aadhaar number must be exactly 12 digits.',
            'pan_no.size' => 'PAN must be exactly 10 characters.',
            'pan_no.regex' => 'PAN format is invalid.',
        ]));

        $driver->update($validated);

        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully.');
    }
}
