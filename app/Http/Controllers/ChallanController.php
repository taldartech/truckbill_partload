<?php

namespace App\Http\Controllers;

use App\Models\Challan;
use App\Models\Company;
use App\Models\Station;
use App\Models\Driver;
use App\Models\Truck;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ChallanController extends Controller implements HasMiddleware
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
        $challans = Challan::with(['company', 'fromStation', 'toStation', 'driver', 'truck'])->latest()->paginate(10);
        return Inertia::render('Challans/Index', [
            'challans' => $challans
        ]);
    }

    public function create()
    {
        $company_id = auth()->check() && auth()->user()->company_id ? auth()->user()->company_id : 1;
        $initial = \App\Models\Setting::where('company_id', $company_id)->where('key', 'challan_initial')->value('value') ?? 'CHL-';
        $lastChallan = \App\Models\Challan::where('company_id', $company_id)->orderBy('id', 'desc')->first();
        $nextId = $lastChallan ? $lastChallan->id + 1 : 1;
        $nextNumber = $initial . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return Inertia::render('Challans/Create', [
            'companies' => Company::all(),
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'bilties' => \App\Models\Bilty::with(['biltyDetails.good', 'consignee'])->where('status', '!=', 'Delivered')->get(),
            'agents' => Agent::orderBy('name')->get(['id', 'name']),
            'next_challan_number' => $nextNumber
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
            'challan_number' => 'nullable|string',
            'date' => 'nullable|date',
            'labour_charge' => 'nullable|numeric',
            'kat' => 'nullable|numeric',
            'home_delivery_charge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'add_to_main_challan' => 'nullable|boolean',
            'truck_freight' => 'nullable|numeric',
            'cash_received' => 'nullable|numeric',
            'cash_payment' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'pay_by' => 'nullable|string',
            'delivery_at' => 'nullable|string',
            'total_weight' => 'nullable|numeric',
            'total_packages' => 'nullable|numeric',
            'to_pay_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'crossing_freight' => 'nullable|numeric',
            'cartage_dalali_pf' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'status' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.bilty_id' => 'required|exists:bilties,id',
        ]);

        $company_id = auth()->check() && auth()->user()->company_id ? auth()->user()->company_id : 1;
        $validated['company_id'] = $validated['company_id'] ?? $company_id;
        
        $details = $validated['details'] ?? [];
        unset($validated['details']);

        $challan = Challan::create($validated);

        foreach ($details as $detail) {
            $bilty = \App\Models\Bilty::find($detail['bilty_id']);
            if ($bilty) {
                $challan->details()->create([
                    'company_id' => $challan->company_id,
                    'bilty_id' => $bilty->id,
                    'to_station_id' => $bilty->to_station_id,
                ]);
            }
        }

        return redirect()->route('challans.index')->with('success', 'Challan created successfully.');
    }

    public function edit(Challan $challan)
    {
        $challan->load('details.bilty.biltyDetails.good', 'details.bilty.consignee');
        
        // Fetch all bilties that are not delivered OR already in this challan
        $existingBiltyIds = $challan->details->pluck('bilty_id')->toArray();
        $bilties = \App\Models\Bilty::with(['biltyDetails.good', 'consignee'])
            ->where('status', '!=', 'Delivered')
            ->orWhereIn('id', $existingBiltyIds)
            ->get();

        return Inertia::render('Challans/Edit', [
            'challan' => $challan,
            'companies' => Company::all(),
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'bilties' => $bilties,
            'agents' => Agent::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Challan $challan)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'challan_number' => 'nullable|string',
            'date' => 'nullable|date',
            'labour_charge' => 'nullable|numeric',
            'kat' => 'nullable|numeric',
            'home_delivery_charge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'add_to_main_challan' => 'nullable|boolean',
            'truck_freight' => 'nullable|numeric',
            'cash_received' => 'nullable|numeric',
            'cash_payment' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'pay_by' => 'nullable|string',
            'delivery_at' => 'nullable|string',
            'total_weight' => 'nullable|numeric',
            'total_packages' => 'nullable|numeric',
            'to_pay_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'crossing_freight' => 'nullable|numeric',
            'cartage_dalali_pf' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'status' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.bilty_id' => 'required|exists:bilties,id',
        ]);

        $details = $validated['details'] ?? [];
        unset($validated['details']);

        $challan->update($validated);
        
        $challan->details()->delete(); // Clear old details
        foreach ($details as $detail) {
            $bilty = \App\Models\Bilty::find($detail['bilty_id']);
            if ($bilty) {
                $challan->details()->create([
                    'company_id' => $challan->company_id,
                    'bilty_id' => $bilty->id,
                    'to_station_id' => $bilty->to_station_id,
                ]);
            }
        }

        return redirect()->route('challans.index')->with('success', 'Challan updated successfully.');
    }

    public function destroy(Challan $challan)
    {
        $challan->delete();
        return redirect()->route('challans.index')->with('success', 'Challan deleted successfully.');
    }

    public function print(Challan $challan)
    {
        $challan->load(['fromStation', 'toStation', 'truck', 'driver', 'details.bilty.biltyDetails', 'details.bilty.consignee', 'details.bilty.toStation']);
        $company = \App\Models\Company::find($challan->company_id);
        
        return view('prints.challan', compact('challan', 'company'));
    }
}