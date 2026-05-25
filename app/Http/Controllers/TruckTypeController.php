<?php

namespace App\Http\Controllers;

use App\Models\TruckType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TruckTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('TruckTypes/Index', [
            'truckTypes' => TruckType::orderBy('id', 'desc')->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if (auth()->check() && auth()->user()->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        }

        TruckType::create($validated);

        return redirect()->back()->with('success', 'Truck type created successfully.');
    }

    public function update(Request $request, TruckType $truckType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $truckType->update($validated);

        return redirect()->back()->with('success', 'Truck type updated successfully.');
    }

    public function destroy(TruckType $truckType)
    {
        $truckType->delete();
        return redirect()->back()->with('success', 'Truck type deleted successfully.');
    }
}
