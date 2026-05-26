<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\Company;
use App\Models\Party;
use App\Models\Station;
use App\Models\Good;
use App\Models\PackageType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BiltyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view bilties', only: ['index', 'show']),
            new Middleware('permission:create bilties', only: ['create', 'store']),
            new Middleware('permission:edit bilties', only: ['edit', 'update']),
            new Middleware('permission:delete bilties', only: ['destroy']),
        ];
    }
    public function index()
    {
        $bilties = Bilty::with(['company', 'consignor', 'consignee', 'fromStation', 'toStation'])->latest()->paginate(10);
        return Inertia::render('Bilties/Index', [
            'bilties' => $bilties
        ]);
    }

    public function create()
    {
        $company_id = auth()->check() && auth()->user()->company_id ? auth()->user()->company_id : 1;
        $initial = \App\Models\Setting::where('company_id', $company_id)->where('key', 'bilty_initial')->value('value') ?? 'BLT-';
        $lastBilty = \App\Models\Bilty::where('company_id', $company_id)->orderBy('id', 'desc')->first();
        $nextId = $lastBilty ? $lastBilty->id + 1 : 1;
        $nextNumber = $initial . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return Inertia::render('Bilties/Create', [
            'companies' => Company::all(),
            'parties' => Party::all(),
            'stations' => Station::all(),
            'goods' => Good::all(),
            'package_types' => PackageType::all(),
            'next_bilty_number' => $nextNumber
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'consignor_id' => 'required|exists:parties,id',
            'consignee_id' => 'required|exists:parties,id',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'bilty_number' => 'nullable|string',
            'date' => 'nullable|date',
            'freight' => 'nullable|numeric',
            'freight_rate' => 'nullable|numeric',
            'freight_type' => 'nullable|string|in:Fixed,Per Package,Per KG',
            'bilty_charge' => 'nullable|numeric',
            'surcharge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'handling_charge' => 'nullable|numeric',
            'risk_dd_charge' => 'nullable|numeric',
            'cartage' => 'nullable|numeric',
            'cartage_type' => 'nullable|string',
            'cartage_due_with_whom' => 'nullable|string',
            'less_advance_received' => 'nullable|numeric',
            'previous_freight' => 'nullable|numeric',
            'previous_freight_no' => 'nullable|string',
            'gst' => 'nullable|numeric',
            'cgst' => 'nullable|numeric',
            'sgst' => 'nullable|numeric',
            'igst' => 'nullable|numeric',
            'gst_liability_by' => 'nullable|string',
            'weight_actual' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'private_marka' => 'nullable|string',
            'delivery_at' => 'nullable|string',
            'eway_bill_no' => 'nullable|string',
            'eway_bill_expiry_date' => 'nullable|date',
            'declared_value' => 'nullable|numeric',
            'payment_type' => 'nullable|string',
            'advance_payment_received' => 'nullable|numeric',
            'payment_mode' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'status' => 'nullable|string',
            'details' => 'nullable|array'
        ]);

        DB::transaction(function () use ($validated) {
            $details = $validated['details'] ?? [];
            unset($validated['details']);

            $bilty = Bilty::create($validated);

            foreach ($details as $item) {
                if (!empty($item['good_id'])) {
                    $bilty->biltyDetails()->create([
                        'company_id' => $bilty->company_id,
                        'good_id' => $item['good_id'],
                        'package_type_id' => $item['package_type_id'],
                        'no_of_packages' => $item['no_of_packages'],
                        'gross_weight' => $item['gross_weight'],
                        'rate' => $item['rate'],
                        'rate_type' => $item['rate_type'] ?? 'Fixed'
                    ]);
                }
            }
        });

        return redirect()->route('bilties.index')->with('success', 'Bilty created successfully.');
    }

    public function edit(Bilty $bilty)
    {
        $bilty->load('biltyDetails');
        return Inertia::render('Bilties/Edit', [
            'bilty' => $bilty,
            'companies' => Company::all(),
            'parties' => Party::all(),
            'stations' => Station::all(),
            'goods' => Good::all(),
            'package_types' => PackageType::all()
        ]);
    }

    public function update(Request $request, Bilty $bilty)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'consignor_id' => 'required|exists:parties,id',
            'consignee_id' => 'required|exists:parties,id',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'bilty_number' => 'nullable|string',
            'date' => 'nullable|date',
            'freight' => 'nullable|numeric',
            'freight_rate' => 'nullable|numeric',
            'freight_type' => 'nullable|string|in:Fixed,Per Package,Per KG',
            'bilty_charge' => 'nullable|numeric',
            'surcharge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'handling_charge' => 'nullable|numeric',
            'risk_dd_charge' => 'nullable|numeric',
            'cartage' => 'nullable|numeric',
            'cartage_type' => 'nullable|string',
            'cartage_due_with_whom' => 'nullable|string',
            'less_advance_received' => 'nullable|numeric',
            'previous_freight' => 'nullable|numeric',
            'previous_freight_no' => 'nullable|string',
            'gst' => 'nullable|numeric',
            'cgst' => 'nullable|numeric',
            'sgst' => 'nullable|numeric',
            'igst' => 'nullable|numeric',
            'gst_liability_by' => 'nullable|string',
            'weight_actual' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'private_marka' => 'nullable|string',
            'delivery_at' => 'nullable|string',
            'eway_bill_no' => 'nullable|string',
            'eway_bill_expiry_date' => 'nullable|date',
            'declared_value' => 'nullable|numeric',
            'payment_type' => 'nullable|string',
            'advance_payment_received' => 'nullable|numeric',
            'payment_mode' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'status' => 'nullable|string',
            'details' => 'nullable|array'
        ]);

        DB::transaction(function () use ($validated, $bilty) {
            $details = $validated['details'] ?? [];
            unset($validated['details']);

            $bilty->update($validated);
            $bilty->biltyDetails()->delete();

            foreach ($details as $item) {
                if (!empty($item['good_id'])) {
                    $bilty->biltyDetails()->create([
                        'company_id' => $bilty->company_id,
                        'good_id' => $item['good_id'],
                        'package_type_id' => $item['package_type_id'],
                        'no_of_packages' => $item['no_of_packages'],
                        'gross_weight' => $item['gross_weight'],
                        'rate' => $item['rate'],
                        'rate_type' => $item['rate_type'] ?? 'Fixed'
                    ]);
                }
            }
        });

        return redirect()->route('bilties.index')->with('success', 'Bilty updated successfully.');
    }

    public function destroy(Bilty $bilty)
    {
        $bilty->delete();
        return redirect()->route('bilties.index')->with('success', 'Bilty deleted successfully.');
    }

    public function print(Bilty $bilty)
    {
        $bilty->load(['fromStation', 'toStation', 'consignor', 'consignee', 'biltyDetails.good', 'biltyDetails.packageType', 'ewayBills']);
        $company = \App\Models\Company::find($bilty->company_id);
        
        return view('prints.bilty', compact('bilty', 'company'));
    }
}