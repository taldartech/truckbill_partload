<?php

namespace App\Http\Controllers;

use App\Models\LoadingReport;
use App\Models\Station;
use App\Models\Truck;
use App\Models\Driver;
use App\Models\Bilty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LoadingReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reports = LoadingReport::with(['fromStation', 'toStation', 'truck', 'driver'])->orderBy('id', 'desc')->paginate(10);
        return Inertia::render('LoadingReports/Index', [
            'reports' => $reports
        ]);
    }

    public function create()
    {
        return Inertia::render('LoadingReports/Create', [
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'drivers' => Driver::all(),
            'bilties' => Bilty::with(['biltyDetails', 'consignee'])->where('status', '!=', 'Delivered')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'truck_id' => 'required|exists:trucks,id',
            'driver_id' => 'required|exists:drivers,id',
            'date' => 'required|date',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.bilty_id' => 'required|exists:bilties,id',
            'details.*.good_id' => 'required|exists:goods,id',
            'details.*.package_type_id' => 'required|exists:package_types,id',
            'details.*.consignee_id' => 'required|exists:parties,id',
            'details.*.no_of_packages' => 'nullable|numeric',
            'details.*.gross_weight' => 'nullable|numeric'
        ]);

        if (auth()->check() && auth()->user()->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        } else {
            $validated['company_id'] = 1; // Default for testing if not auth
        }

        DB::transaction(function () use ($validated) {
            $details = $validated['details'];
            unset($validated['details']);

            // Calculate totals
            $validated['no_of_packages'] = collect($details)->sum('no_of_packages');
            $validated['gross_weight'] = collect($details)->sum('gross_weight');

            $report = LoadingReport::create($validated);

            foreach ($details as $item) {
                $item['company_id'] = $report->company_id;
                $report->details()->create($item);
            }

            // Update Truck Status
            Truck::where('id', $validated['truck_id'])->update(['status' => 'Booked']);
        });

        return redirect()->route('loading-reports.index')->with('success', 'Loading Report created successfully.');
    }

    public function edit(LoadingReport $loadingReport)
    {
        $loadingReport->load('details');
        return Inertia::render('LoadingReports/Edit', [
            'report' => $loadingReport,
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'drivers' => Driver::all(),
            'bilties' => Bilty::with(['biltyDetails', 'consignee'])->get()
        ]);
    }

    public function update(Request $request, LoadingReport $loadingReport)
    {
        $validated = $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'truck_id' => 'required|exists:trucks,id',
            'driver_id' => 'required|exists:drivers,id',
            'date' => 'required|date',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.bilty_id' => 'required|exists:bilties,id',
            'details.*.good_id' => 'required|exists:goods,id',
            'details.*.package_type_id' => 'required|exists:package_types,id',
            'details.*.consignee_id' => 'required|exists:parties,id',
            'details.*.no_of_packages' => 'nullable|numeric',
            'details.*.gross_weight' => 'nullable|numeric'
        ]);

        if (auth()->check() && auth()->user()->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        }

        DB::transaction(function () use ($validated, $loadingReport) {
            $details = $validated['details'];
            unset($validated['details']);

            $validated['no_of_packages'] = collect($details)->sum('no_of_packages');
            $validated['gross_weight'] = collect($details)->sum('gross_weight');

            $loadingReport->update($validated);
            
            $loadingReport->details()->delete();

            foreach ($details as $item) {
                $item['company_id'] = $loadingReport->company_id;
                $loadingReport->details()->create($item);
            }
        });

        return redirect()->route('loading-reports.index')->with('success', 'Loading Report updated successfully.');
    }

    public function destroy(LoadingReport $loadingReport)
    {
        $loadingReport->delete();
        return redirect()->route('loading-reports.index')->with('success', 'Loading Report deleted successfully.');
    }
}
