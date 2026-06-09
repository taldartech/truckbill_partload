<?php

namespace App\Http\Controllers;

use App\Models\LoadingReport;
use App\Models\Station;
use App\Models\Truck;
use App\Models\Driver;
use App\Models\Bilty;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LoadingReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view loading_reports', only: ['index', 'show', 'print']),
            new Middleware('permission:create loading_reports', only: ['create', 'store']),
            new Middleware('permission:edit loading_reports', only: ['edit', 'update']),
            new Middleware('permission:delete loading_reports', only: ['destroy']),
        ];
    }
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

        DB::transaction(function () use ($validated) {
            $details = $validated['details'];
            unset($validated['details']);

            // Calculate totals
            $validated['no_of_packages'] = collect($details)->sum('no_of_packages');
            $validated['gross_weight'] = collect($details)->sum('gross_weight');

            $report = LoadingReport::create($validated);

            foreach ($details as $item) {
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

        DB::transaction(function () use ($validated, $loadingReport) {
            $details = $validated['details'];
            unset($validated['details']);

            $validated['no_of_packages'] = collect($details)->sum('no_of_packages');
            $validated['gross_weight'] = collect($details)->sum('gross_weight');

            $loadingReport->update($validated);
            
            $loadingReport->details()->delete();

            foreach ($details as $item) {
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

    public function print(LoadingReport $loading_report)
    {
        $loading_report->load([
            'fromStation',
            'toStation',
            'truck',
            'driver',
            'details.bilty.toStation',
            'details.bilty.biltyDetails.good',
            'details.good',
            'details.consignee',
        ]);

        $company = \App\Models\Company::find($loading_report->company_id);
        $printData = $this->buildPrintData($loading_report);

        return view('prints.loading_report', compact('loading_report', 'company', 'printData'));
    }

    private function buildPrintData(LoadingReport $report): array
    {
        $rows = $report->details->map(function ($detail) {
            $bilty = $detail->bilty;
            $goodsName = $detail->good?->name;

            if (!$goodsName && $bilty?->biltyDetails) {
                $goodsName = $bilty->biltyDetails
                    ->map(fn ($bd) => $bd->good?->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            }

            return [
                'to_station_id' => $bilty?->to_station_id ?? 0,
                'to_station' => $bilty?->toStation?->name ?? '—',
                'bilty_number' => $bilty?->bilty_number ?? '—',
                'private_marka' => $bilty?->private_marka ?? '',
                'nugs' => (float) ($detail->no_of_packages ?? 0),
                'goods_name' => $goodsName ?: '—',
                'consignee_name' => $detail->consignee?->name ?? '—',
                'weight' => (float) ($detail->gross_weight ?? 0),
            ];
        });

        $groups = $rows
            ->groupBy('to_station_id')
            ->sortBy(fn ($group) => $group->first()['to_station'] ?? '')
            ->map(function ($groupRows, $stationId) {
                $stationName = $groupRows->first()['to_station'] ?? '—';
                $items = $groupRows->values()->map(function ($row, $index) {
                    $row['serial'] = $index + 1;

                    return $row;
                });

                return [
                    'station_name' => $stationName,
                    'rows' => $items,
                    'subtotal' => [
                        'nugs' => $items->sum('nugs'),
                        'weight' => $items->sum('weight'),
                    ],
                ];
            })
            ->values();

        return [
            'groups' => $groups,
            'grand_total' => [
                'nugs' => $rows->sum('nugs'),
                'weight' => $rows->sum('weight'),
            ],
        ];
    }
}
