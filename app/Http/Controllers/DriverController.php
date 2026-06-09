<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\City;
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
        $drivers = Driver::with('company')->latest()->paginate(10);

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
            'name' => 'nullable|string',
            'father_name' => 'nullable|string',
            'mobile' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'license_number' => 'nullable|string',
            'license_expiry_date' => 'nullable|date',
            'aadhar_no' => 'nullable|string',
            'pan_no' => 'nullable|string',
            'rto_name' => 'nullable|string',
            'rto_state' => 'nullable|string',
            'license_verification' => 'nullable|string',
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
            'name' => 'nullable|string',
            'father_name' => 'nullable|string',
            'mobile' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'license_number' => 'nullable|string',
            'license_expiry_date' => 'nullable|date',
            'aadhar_no' => 'nullable|string',
            'pan_no' => 'nullable|string',
            'rto_name' => 'nullable|string',
            'rto_state' => 'nullable|string',
            'license_verification' => 'nullable|string',
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
