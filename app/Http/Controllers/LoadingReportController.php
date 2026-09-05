<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\Company;
use App\Models\InwardRegisterDetail;
use App\Models\LoadingReport;
use App\Models\LoadingReportDetail;
use App\Models\Station;
use App\Models\Truck;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            'bilties' => $this->pendingBilties(),
            'inward_details' => $this->pendingInwardDetails(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedReport($request);
        $this->validateLoadingDetails($validated['details']);

        $report = DB::transaction(function () use ($validated) {
            $details = $validated['details'];
            unset($validated['details']);

            $normalized = $this->normalizeDetails($details);
            $validated['no_of_packages'] = collect($normalized)->sum('no_of_packages');
            $validated['gross_weight'] = collect($normalized)->sum('gross_weight');

            $report = LoadingReport::create($validated);
            $this->syncLoadingReportDetails($report, $normalized);

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

    public function show(LoadingReport $loadingReport)
    {
        $loadingReport->load([
            'fromStation',
            'toStation',
            'truck',
            'details.bilty.toStation',
            'details.inwardRegisterDetail.toStation',
            'details.inwardRegisterDetail.good',
            'details.inwardRegisterDetail.consignee',
            'details.good',
            'details.packageType',
            'details.consignee',
        ]);

        return Inertia::render('LoadingReports/Show', [
            'report' => $loadingReport,
        ]);
    }

    public function edit(LoadingReport $loadingReport)
    {
        $loadingReport->load([
            'details.bilty.biltyDetails.good',
            'details.bilty.consignee',
            'details.inwardRegisterDetail.good',
            'details.inwardRegisterDetail.consignee',
            'details.inwardRegisterDetail.toStation',
            'details.good',
            'details.packageType',
            'details.consignee',
        ]);

        return Inertia::render('LoadingReports/Edit', [
            'report' => $loadingReport,
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'bilties' => $this->pendingBilties($loadingReport->id),
            'inward_details' => $this->pendingInwardDetails($loadingReport->id),
        ]);
    }

    public function update(Request $request, LoadingReport $loadingReport)
    {
        $validated = $this->validatedReport($request);
        $this->validateLoadingDetails($validated['details']);

        DB::transaction(function () use ($validated, $loadingReport) {
            $details = $validated['details'];
            unset($validated['details']);

            $normalized = $this->normalizeDetails($details);
            $validated['no_of_packages'] = collect($normalized)->sum('no_of_packages');
            $validated['gross_weight'] = collect($normalized)->sum('gross_weight');

            $loadingReport->update($validated);
            $this->syncLoadingReportDetails($loadingReport, $normalized);
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'loading-reports.index',
            'loading-reports.print',
            $loadingReport,
            'Loading Report updated successfully.',
        );
    }

    public function destroy(Request $request, LoadingReport $loadingReport)
    {
        $loadingReport->update([
            'delete_remark' => $request->delete_remark,
            'deleted_by' => Auth::id(),
        ]);

        $loadingReport->delete();

        return redirect()
            ->route('loading-reports.index')
            ->with('success', 'Loading Report deleted successfully.');
    }

    public function print(LoadingReport $loading_report)
    {
        $loading_report->load([
            'fromStation',
            'toStation',
            'truck',
            'details.bilty.toStation',
            'details.bilty.biltyDetails.good',
            'details.inwardRegisterDetail.toStation',
            'details.inwardRegisterDetail.good',
            'details.inwardRegisterDetail.consignee',
            'details.good',
            'details.consignee',
        ]);

        $company = Company::find($loading_report->company_id);
        $printData = $this->buildPrintData($loading_report);

        return view('prints.loading_report', compact('loading_report', 'company', 'printData'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedReport(Request $request): array
    {
        return $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'truck_id' => 'required|exists:trucks,id',
            'driver_name' => 'required|string|max:255',
            'date' => 'required|date',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.row_type' => 'nullable|in:bilty,inward',
            'details.*.bilty_id' => 'nullable|exists:bilties,id',
            'details.*.inward_register_detail_id' => 'nullable|exists:inward_register_details,id',
            'details.*.good_id' => 'nullable|exists:goods,id',
            'details.*.package_type_id' => 'nullable|exists:package_types,id',
            'details.*.consignee_id' => 'nullable|exists:parties,id',
            'details.*.no_of_packages' => 'nullable|numeric',
            'details.*.gross_weight' => 'nullable|numeric',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $details
     */
    private function validateLoadingDetails(array $details): void
    {
        foreach ($details as $index => $detail) {
            $hasBilty = ! empty($detail['bilty_id']);
            $hasInward = ! empty($detail['inward_register_detail_id']);

            if ($hasBilty === $hasInward) {
                throw ValidationException::withMessages([
                    "details.{$index}.bilty_id" => 'Each line must reference either a bilty or an inward GR.',
                ]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $details
     * @return list<array<string, mixed>>
     */
    private function normalizeDetails(array $details): array
    {
        $normalized = [];

        foreach ($details as $detail) {
            if (! empty($detail['bilty_id'])) {
                $bilty = Bilty::with(['biltyDetails.good', 'consignee'])->find($detail['bilty_id']);
                if (! $bilty) {
                    continue;
                }

                $packages = 0.0;
                $weight = 0.0;
                $goodId = $detail['good_id'] ?? null;
                $packageTypeId = $detail['package_type_id'] ?? null;

                foreach ($bilty->biltyDetails as $biltyDetail) {
                    $packages += (float) ($biltyDetail->no_of_packages ?? 0);
                    $weight += (float) ($biltyDetail->gross_weight ?? 0);
                    $goodId ??= $biltyDetail->good_id;
                    $packageTypeId ??= $biltyDetail->package_type_id;
                }

                $normalized[] = [
                    'bilty_id' => $bilty->id,
                    'inward_register_detail_id' => null,
                    'good_id' => $goodId ?? $bilty->biltyDetails->first()?->good_id,
                    'package_type_id' => $packageTypeId,
                    'consignee_id' => $detail['consignee_id'] ?? $bilty->consignee_id,
                    'no_of_packages' => $detail['no_of_packages'] ?? $packages,
                    'gross_weight' => $detail['gross_weight'] ?? $weight,
                ];

                continue;
            }

            if (! empty($detail['inward_register_detail_id'])) {
                $inward = InwardRegisterDetail::with(['good', 'consignee'])->find($detail['inward_register_detail_id']);
                if (! $inward || $inward->disposition !== 'forward') {
                    continue;
                }

                $normalized[] = [
                    'bilty_id' => null,
                    'inward_register_detail_id' => $inward->id,
                    'good_id' => $detail['good_id'] ?? $inward->good_id,
                    'package_type_id' => $detail['package_type_id'] ?? null,
                    'consignee_id' => $detail['consignee_id'] ?? $inward->consignee_id,
                    'no_of_packages' => $detail['no_of_packages'] ?? $inward->no_of_packages,
                    'gross_weight' => $detail['gross_weight'] ?? $inward->weight,
                ];
            }
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'details' => 'Add at least one bilty or inward GR.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $details
     */
    private function syncLoadingReportDetails(LoadingReport $report, array $details): void
    {
        $report->details()->delete();

        foreach ($details as $item) {
            $report->details()->create($item);
        }
    }

    private function pendingBilties(?int $loadingReportId = null)
    {
        $query = Bilty::with(['biltyDetails.good', 'consignee', 'toStation']);

        if ($loadingReportId) {
            $linkedIds = LoadingReportDetail::query()
                ->where('loading_report_id', $loadingReportId)
                ->whereNotNull('bilty_id')
                ->pluck('bilty_id');

            $query->where(function ($builder) use ($linkedIds) {
                $builder->whereNotIn('status', ['Delivered', 'Cancelled']);

                if ($linkedIds->isNotEmpty()) {
                    $builder->orWhereIn('id', $linkedIds);
                }
            });
        } else {
            $query->whereNotIn('status', ['Delivered', 'Cancelled']);
        }

        return $query->get();
    }

    private function pendingInwardDetails(?int $loadingReportId = null)
    {
        $query = InwardRegisterDetail::with(['inwardRegister', 'toStation', 'consignee', 'good'])
            ->where('disposition', 'forward')
            ->whereNotNull('to_station_id');

        if ($loadingReportId) {
            $linkedIds = LoadingReportDetail::query()
                ->where('loading_report_id', $loadingReportId)
                ->whereNotNull('inward_register_detail_id')
                ->pluck('inward_register_detail_id');

            $query->where(function ($builder) use ($linkedIds) {
                $builder->where('status', 'pending');

                if ($linkedIds->isNotEmpty()) {
                    $builder->orWhereIn('id', $linkedIds);
                }
            });
        } else {
            $query->where('status', 'pending');
        }

        return $query->get();
    }

    private function buildPrintData(LoadingReport $report): array
    {
        $rows = $report->details->map(function ($detail) {
            $bilty = $detail->bilty;
            $inward = $detail->inwardRegisterDetail;
            $goodsName = $detail->good?->name;

            if (! $goodsName && $bilty?->biltyDetails) {
                $goodsName = $bilty->biltyDetails
                    ->map(fn ($bd) => $bd->good?->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            }

            if (! $goodsName) {
                $goodsName = $inward?->good?->name;
            }

            $toStation = $bilty?->toStation ?? $inward?->toStation;
            $documentNumber = $bilty?->bilty_number
                ?? ($inward?->gr_no ?: ($inward ? 'GR-'.$inward->id : null));

            return [
                'to_station_id' => $toStation?->id ?? 0,
                'to_station' => $toStation?->name ?? '—',
                'bilty_number' => $documentNumber ?? '—',
                'private_marka' => $bilty?->private_marka ?? ($inward?->private_marka ?? ''),
                'nugs' => (float) ($detail->no_of_packages ?? 0),
                'goods_name' => $goodsName ?: '—',
                'consignee_name' => $detail->consignee?->name
                    ?? $bilty?->consignee?->name
                    ?? $inward?->consignee?->name
                    ?? '—',
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
