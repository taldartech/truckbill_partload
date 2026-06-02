<?php

namespace App\Http\Controllers;

use App\Models\MainChallan;
use App\Models\Challan;
use App\Models\Company;
use App\Models\Station;
use App\Models\Driver;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class MainChallanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view challans', only: ['index', 'show']),
            new Middleware('permission:create challans', only: ['create', 'store']),
            new Middleware('permission:edit challans', only: ['edit', 'update']),
            new Middleware('permission:delete challans', only: ['destroy']),
        ];
    }

    public function index()
    {
        $main_challans = MainChallan::with(['company', 'fromStation', 'toStation', 'driver', 'truck'])->latest()->paginate(10);
        return Inertia::render('MainChallans/Index', [
            'main_challans' => $main_challans
        ]);
    }

    public function create()
    {
        $company_id = auth()->check() && auth()->user()->company_id ? auth()->user()->company_id : 1;
        $initial = \App\Models\Setting::where('company_id', $company_id)->where('key', 'main_challan_initial')->value('value') ?? 'MCH-';
        $lastChallan = \App\Models\MainChallan::where('company_id', $company_id)->orderBy('id', 'desc')->first();
        $nextId = $lastChallan ? $lastChallan->id + 1 : 1;
        $nextNumber = $initial . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // Fetch challans that are NOT added to a main challan yet
        $available_challans = Challan::whereDoesntHave('details', function ($query) {
            // we only want challans that aren't already linked to a main challan
            // Wait, details in MainChallan link to Challans
        })->get();
        // Since we don't have a direct back-relationship, we can check main_challan_details
        $used_challan_ids = \App\Models\MainChallanDetail::pluck('challan_id')->toArray();
        $available_challans = Challan::with(['details.bilty.biltyDetails.good'])
            ->whereNotIn('id', $used_challan_ids)
            ->get();

        return Inertia::render('MainChallans/Create', [
            'companies' => Company::all(),
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'challans' => $available_challans,
            'next_main_challan_number' => $nextNumber
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'main_challan_number' => 'nullable|string',
            'date' => 'nullable|date',
            'labour_charge' => 'nullable|numeric',
            'kat' => 'nullable|numeric',
            'home_delivery_charge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'truck_freight' => 'nullable|numeric',
            'cash_received' => 'nullable|numeric',
            'cash_paid' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'total_balance_type' => 'nullable|string',
            'pay_by' => 'nullable|string',
            'total_weight' => 'nullable|numeric',
            'total_packages' => 'nullable|numeric',
            'to_pay_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'crossing_freight' => 'nullable|numeric',
            'cartage_dalali_pf' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'status' => 'nullable|string',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.challan_id' => 'required|exists:challans,id',
        ]);

        $company_id = auth()->check() && auth()->user()->company_id ? auth()->user()->company_id : 1;
        $validated['company_id'] = $validated['company_id'] ?? $company_id;
        
        $details = $validated['details'] ?? [];
        unset($validated['details']);

        $main_challan = MainChallan::create($validated);

        foreach ($details as $detail) {
            $challan = Challan::find($detail['challan_id']);
            if ($challan) {
                $main_challan->details()->create([
                    'company_id' => $main_challan->company_id,
                    'challan_id' => $challan->id,
                ]);
            }
        }

        return redirect()->route('main-challans.index')->with('success', 'Main Challan created successfully.');
    }

    public function edit(MainChallan $main_challan)
    {
        $main_challan->load('details.challan.details.bilty.biltyDetails.good');
        
        $existingChallanIds = $main_challan->details->pluck('challan_id')->toArray();
        $used_challan_ids = \App\Models\MainChallanDetail::where('main_challan_id', '!=', $main_challan->id)->pluck('challan_id')->toArray();
        
        $available_challans = Challan::with(['details.bilty.biltyDetails.good'])
            ->whereNotIn('id', $used_challan_ids)
            ->get();

        return Inertia::render('MainChallans/Edit', [
            'main_challan' => $main_challan,
            'companies' => Company::all(),
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'challans' => $available_challans,
        ]);
    }

    public function update(Request $request, MainChallan $main_challan)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'main_challan_number' => 'nullable|string',
            'date' => 'nullable|date',
            'labour_charge' => 'nullable|numeric',
            'kat' => 'nullable|numeric',
            'home_delivery_charge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'truck_freight' => 'nullable|numeric',
            'cash_received' => 'nullable|numeric',
            'cash_paid' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'total_balance_type' => 'nullable|string',
            'pay_by' => 'nullable|string',
            'total_weight' => 'nullable|numeric',
            'total_packages' => 'nullable|numeric',
            'to_pay_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'crossing_freight' => 'nullable|numeric',
            'cartage_dalali_pf' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'status' => 'nullable|string',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.challan_id' => 'required|exists:challans,id',
        ]);

        $details = $validated['details'] ?? [];
        unset($validated['details']);

        $main_challan->update($validated);
        
        $main_challan->details()->delete(); // Clear old details
        foreach ($details as $detail) {
            $challan = Challan::find($detail['challan_id']);
            if ($challan) {
                $main_challan->details()->create([
                    'company_id' => $main_challan->company_id,
                    'challan_id' => $challan->id,
                ]);
            }
        }

        return redirect()->route('main-challans.index')->with('success', 'Main Challan updated successfully.');
    }

    public function destroy(MainChallan $main_challan)
    {
        $main_challan->delete();
        return redirect()->route('main-challans.index')->with('success', 'Main Challan deleted successfully.');
    }
}
