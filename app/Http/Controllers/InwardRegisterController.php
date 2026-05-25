<?php

namespace App\Http\Controllers;

use App\Models\InwardRegister;
use App\Models\Station;
use App\Models\Challan;
use App\Models\Driver;
use App\Models\Truck;
use App\Models\Good;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InwardRegisterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $registers = InwardRegister::with(['fromStation', 'toStation', 'challan', 'truck', 'driver', 'good'])
            ->orderBy('id', 'desc')
            ->paginate(10);
            
        return Inertia::render('InwardRegisters/Index', [
            'registers' => $registers
        ]);
    }

    public function create()
    {
        return Inertia::render('InwardRegisters/Create', [
            'stations' => Station::all(),
            'challans' => Challan::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'goods' => Good::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inward_register_no' => 'nullable|string|max:255',
            'date' => 'required|date',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'challan_id' => 'required|exists:challans,id',
            'challan_date' => 'required|date',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'goods_ready_no' => 'nullable|string|max:255',
            'goods_ready_date' => 'nullable|date',
            'good_id' => 'required|exists:goods,id'
        ]);

        if (auth()->check() && auth()->user()->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        } else {
            $validated['company_id'] = 1;
        }

        InwardRegister::create($validated);

        return redirect()->route('inward-registers.index')->with('success', 'Inward Register created successfully.');
    }

    public function edit(InwardRegister $inwardRegister)
    {
        return Inertia::render('InwardRegisters/Edit', [
            'register' => $inwardRegister,
            'stations' => Station::all(),
            'challans' => Challan::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'goods' => Good::all()
        ]);
    }

    public function update(Request $request, InwardRegister $inwardRegister)
    {
        $validated = $request->validate([
            'inward_register_no' => 'nullable|string|max:255',
            'date' => 'required|date',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'challan_id' => 'required|exists:challans,id',
            'challan_date' => 'required|date',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'goods_ready_no' => 'nullable|string|max:255',
            'goods_ready_date' => 'nullable|date',
            'good_id' => 'required|exists:goods,id'
        ]);

        $inwardRegister->update($validated);

        return redirect()->route('inward-registers.index')->with('success', 'Inward Register updated successfully.');
    }

    public function destroy(InwardRegister $inwardRegister)
    {
        $inwardRegister->delete();
        return redirect()->route('inward-registers.index')->with('success', 'Inward Register deleted successfully.');
    }
}
