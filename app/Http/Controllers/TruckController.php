<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\Company;
use App\Models\Transporter;
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
        $trucks = Truck::with(['company', 'transporter', 'truckType'])->latest()->paginate(10);
        return Inertia::render('Trucks/Index', [
            'trucks' => $trucks
        ]);
    }

    public function create()
    {
        return Inertia::render('Trucks/Create', [
            'companies' => Company::all(),
            'transporters' => Transporter::all(),
            'truckTypes' => TruckType::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'truck_number' => 'nullable|string',
            'truck_type_id' => 'nullable|exists:truck_types,id',
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
        ]);

        Truck::create($validated);
        return redirect()->route('trucks.index')->with('success', 'Truck created successfully.');
    }

    public function edit(Truck $truck)
    {
        return Inertia::render('Trucks/Edit', [
            'truck' => $truck,
            'companies' => Company::all(),
            'transporters' => Transporter::all(),
            'truckTypes' => TruckType::all(),
        ]);
    }

    public function update(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'truck_number' => 'nullable|string',
            'truck_type_id' => 'nullable|exists:truck_types,id',
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
        ]);

        $truck->update($validated);
        return redirect()->route('trucks.index')->with('success', 'Truck updated successfully.');
    }

    public function destroy(Truck $truck)
    {
        $truck->delete();
        return redirect()->route('trucks.index')->with('success', 'Truck deleted successfully.');
    }
}