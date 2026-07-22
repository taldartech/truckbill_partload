<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\Company;
use App\Models\LoadingReport;
use App\Models\Station;
use App\Models\Truck;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LoadingReportController extends Controller implements HasMiddleware
{
    use RedirectsWithOptionalPrint;

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
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'truck_id' => ['nullable', 'integer', 'exists:trucks,id'],
            'from_station_id' => ['nullable', 'integer', 'exists:stations,id'],
        ]);

        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->endOfMonth()->toDateString();
        $search = trim((string) ($validated['search'] ?? ''));

        $reports = LoadingReport::query()
            ->with(['fromStation', 'toStation', 'truck'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('driver_name', 'like', '%'.$search.'%')
                        ->orWhere('id', 'like', '%'.$search.'%')
                        ->orWhereHas('truck', function ($truckQuery) use ($search) {
                            $truckQuery->where('truck_number', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($fromDate, fn ($query) => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('date', '<=', $toDate))
            ->when(! empty($validated['truck_id']), function ($query) use ($validated) {
                $query->where('truck_id', $validated['truck_id']);
            })
            ->when(! empty($validated['from_station_id']), function ($query) use ($validated) {
                $query->where('from_station_id', $validated['from_station_id']);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('LoadingReports/Index', [
            'reports' => $reports,
            'trucks' => Truck::query()
                ->orderBy('truck_number')
                ->get(['id', 'truck_number'])
                ->map(fn (Truck $truck) => [
                    'id' => $truck->id,
                    'name' => $truck->truck_number,
                    'truck_number' => $truck->truck_number,
                ]),
            'stations' => Station::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'truck_id' => isset($validated['truck_id']) ? (string) $validated['truck_id'] : '',
                'from_station_id' => isset($validated['from_station_id']) ? (string) $validated['from_station_id'] : '',
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('LoadingReports/Create', [
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'bilties' => Bilty::with(['biltyDetails', 'consignee'])->where('status', '!=', 'Delivered')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'truck_id' => 'required|exists:trucks,id',
            'driver_name' => 'required|string|max:255',
            'date' => 'required|date',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.bilty_id' => 'required|exists:bilties,id',
            'details.*.good_id' => 'required|exists:goods,id',
            'details.*.package_type_id' => 'required|exists:package_types,id',
            'details.*.consignee_id' => 'required|exists:parties,id',
            'details.*.no_of_packages' => 'nullable|numeric',
            'details.*.gross_weight' => 'nullable|numeric',
        ]);

        $report = DB::transaction(function () use ($validated) {
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

            return $report;
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'loading-reports.index',
            'loading-reports.print',
            $report,
            'Loading Report created successfully.',
        );
    }

    public function edit(LoadingReport $loadingReport)
    {
        $loadingReport->load('details');

        return Inertia::render('LoadingReports/Edit', [
            'report' => $loadingReport,
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'bilties' => Bilty::with(['biltyDetails', 'consignee'])->get(),
        ]);
    }

    public function update(Request $request, LoadingReport $loadingReport)
    {
        $validated = $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'truck_id' => 'required|exists:trucks,id',
            'driver_name' => 'required|string|max:255',
            'date' => 'required|date',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.bilty_id' => 'required|exists:bilties,id',
            'details.*.good_id' => 'required|exists:goods,id',
            'details.*.package_type_id' => 'required|exists:package_types,id',
            'details.*.consignee_id' => 'required|exists:parties,id',
            'details.*.no_of_packages' => 'nullable|numeric',
            'details.*.gross_weight' => 'nullable|numeric',
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

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'loading-reports.index',
            'loading-reports.print',
            $loadingReport,
            'Loading Report updated successfully.',
        );
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
            'details.bilty.toStation',
            'details.bilty.biltyDetails.good',
            'details.good',
            'details.consignee',
        ]);

        $company = Company::find($loading_report->company_id);
        $printData = $this->buildPrintData($loading_report);

        return view('prints.loading_report', compact('loading_report', 'company', 'printData'));
    }

    private function buildPrintData(LoadingReport $report): array
    {
        $rows = $report->details->map(function ($detail) {
            $bilty = $detail->bilty;
            $goodsName = $detail->good?->name;

            if (! $goodsName && $bilty?->biltyDetails) {
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
