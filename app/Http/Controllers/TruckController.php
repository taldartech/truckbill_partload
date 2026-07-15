<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\TruckType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class TruckController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view trucks', only: ['index', 'show']),
            new Middleware('permission:create trucks', only: ['create', 'store']),
            new Middleware('permission:edit trucks', only: ['edit', 'update']),
            new Middleware('permission:delete trucks', only: ['destroy']),
        ];
    }

    public function index()
    {
        $trucks = Truck::with(['company', 'truckType', 'currentState', 'currentCity', 'currentStation'])->latest()->paginate(10);

        return Inertia::render('Trucks/Index', [
            'trucks' => $trucks,
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Trucks/Create', [
            'truckTypes' => TruckType::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'truck_number' => 'nullable|string|max:20',
            'truck_type_id' => 'nullable|exists:truck_types,id',
            'status' => 'nullable|string|max:50',
            'current_state_id' => 'nullable|exists:states,id',
            'current_city_id' => 'nullable|exists:cities,id',
            'current_station_id' => 'nullable|exists:stations,id',
            'capacity' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:255',
            'owner_mobile' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'owner_email' => 'nullable|string|email|max:255',
            'owner_address' => 'nullable|string|max:500',
            'owner_city' => 'nullable|string|max:100',
            'owner_state' => 'nullable|string|max:100',
            'rc_no' => 'nullable|string|max:50',
            'rc_expiry_date' => 'nullable|date',
            'insurance_no' => 'nullable|string|max:50',
            'insurance_expiry_date' => 'nullable|date',
            'fitness_no' => 'nullable|string|max:50',
            'fitness_expiry_date' => 'nullable|date',
            'pollution_no' => 'nullable|string|max:50',
            'pollution_expiry_date' => 'nullable|date',
            'permit_no' => 'nullable|string|max:50',
            'permit_expiry_date' => 'nullable|date',
        ], [
            'owner_mobile.digits' => 'Owner mobile must be exactly 10 digits.',
            'owner_mobile.regex' => 'Owner mobile must be a valid 10-digit Indian mobile number.',
        ]));

        if (! empty($validated['truck_number'])) {
            $validated['truck_number'] = strtoupper($validated['truck_number']);
        }

        Truck::create($validated);

        return redirect()->route('trucks.index')->with('success', 'Truck created successfully.');
    }

    public function edit(Truck $truck)
    {
        return Inertia::render('Trucks/Edit', [
            'truck' => $truck,
            'truckTypes' => TruckType::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
        ]);
    }

    public function update(Request $request, Truck $truck)
    {
        $validated = $this->stripTenantFields($request->validate([
            'truck_number' => 'nullable|string|max:20',
            'truck_type_id' => 'nullable|exists:truck_types,id',
            'status' => 'nullable|string|max:50',
            'current_state_id' => 'nullable|exists:states,id',
            'current_city_id' => 'nullable|exists:cities,id',
            'current_station_id' => 'nullable|exists:stations,id',
            'capacity' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:255',
            'owner_mobile' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'owner_email' => 'nullable|string|email|max:255',
            'owner_address' => 'nullable|string|max:500',
            'owner_city' => 'nullable|string|max:100',
            'owner_state' => 'nullable|string|max:100',
            'rc_no' => 'nullable|string|max:50',
            'rc_expiry_date' => 'nullable|date',
            'insurance_no' => 'nullable|string|max:50',
            'insurance_expiry_date' => 'nullable|date',
            'fitness_no' => 'nullable|string|max:50',
            'fitness_expiry_date' => 'nullable|date',
            'pollution_no' => 'nullable|string|max:50',
            'pollution_expiry_date' => 'nullable|date',
            'permit_no' => 'nullable|string|max:50',
            'permit_expiry_date' => 'nullable|date',
        ], [
            'owner_mobile.digits' => 'Owner mobile must be exactly 10 digits.',
            'owner_mobile.regex' => 'Owner mobile must be a valid 10-digit Indian mobile number.',
        ]));

        if (! empty($validated['truck_number'])) {
            $validated['truck_number'] = strtoupper($validated['truck_number']);
        }

        $truck->update($validated);

        return redirect()->route('trucks.index')->with('success', 'Truck updated successfully.');
    }

    public function destroy(Truck $truck)
    {
        $truck->delete();

        return redirect()->route('trucks.index')->with('success', 'Truck deleted successfully.');
    }

    public function completeTour(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'current_state_id' => 'required|exists:states,id',
            'current_city_id' => 'required|exists:cities,id',
            'current_station_id' => 'nullable|exists:stations,id',
        ]);

        $truck->update([
            'status' => $validated['status'],
            'current_state_id' => $validated['current_state_id'],
            'current_city_id' => $validated['current_city_id'],
            'current_station_id' => $validated['current_station_id'],
        ]);

        return redirect()->back()->with('success', 'Truck status and location updated successfully.');
    }
}
