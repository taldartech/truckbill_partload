<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\Transporter;
use App\Models\TruckType;
use App\Models\State;
use App\Models\City;
use App\Models\Station;
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
        $trucks = Truck::with(['company', 'transporter', 'truckType', 'currentState', 'currentCity', 'currentStation'])->latest()->paginate(10);
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
            'transporters' => Transporter::all(),
            'truckTypes' => TruckType::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'transporter_id' => 'nullable|exists:transporters,id',
            'truck_number' => 'nullable|string',
            'truck_type_id' => 'nullable|exists:truck_types,id',
            'status' => 'nullable|string',
            'current_state_id' => 'nullable|exists:states,id',
            'current_city_id' => 'nullable|exists:cities,id',
            'current_station_id' => 'nullable|exists:stations,id',
            'capacity' => 'nullable|string',
            'owner_name' => 'nullable|string',
            'owner_mobile' => 'nullable|string',
            'owner_email' => 'nullable|email',
            'owner_address' => 'nullable|string',
            'owner_city' => 'nullable|string',
            'owner_state' => 'nullable|string',
            'rc_no' => 'nullable|string',
            'rc_expiry_date' => 'nullable|date',
            'insurance_no' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'fitness_no' => 'nullable|string',
            'fitness_expiry_date' => 'nullable|date',
            'pollution_no' => 'nullable|string',
            'pollution_expiry_date' => 'nullable|date',
            'permit_no' => 'nullable|string',
            'permit_expiry_date' => 'nullable|date',
        ]));

        Truck::create($validated);
        return redirect()->route('trucks.index')->with('success', 'Truck created successfully.');
    }

    public function edit(Truck $truck)
    {
        return Inertia::render('Trucks/Edit', [
            'truck' => $truck,
            'transporters' => Transporter::all(),
            'truckTypes' => TruckType::all(),
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
        ]);
    }

    public function update(Request $request, Truck $truck)
    {
        $validated = $this->stripTenantFields($request->validate([
            'transporter_id' => 'nullable|exists:transporters,id',
            'truck_number' => 'nullable|string',
            'truck_type_id' => 'nullable|exists:truck_types,id',
            'status' => 'nullable|string',
            'current_state_id' => 'nullable|exists:states,id',
            'current_city_id' => 'nullable|exists:cities,id',
            'current_station_id' => 'nullable|exists:stations,id',
            'capacity' => 'nullable|string',
            'owner_name' => 'nullable|string',
            'owner_mobile' => 'nullable|string',
            'owner_email' => 'nullable|email',
            'owner_address' => 'nullable|string',
            'owner_city' => 'nullable|string',
            'owner_state' => 'nullable|string',
            'rc_no' => 'nullable|string',
            'rc_expiry_date' => 'nullable|date',
            'insurance_no' => 'nullable|string',
            'insurance_expiry_date' => 'nullable|date',
            'fitness_no' => 'nullable|string',
            'fitness_expiry_date' => 'nullable|date',
            'pollution_no' => 'nullable|string',
            'pollution_expiry_date' => 'nullable|date',
            'permit_no' => 'nullable|string',
            'permit_expiry_date' => 'nullable|date',
        ]));

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