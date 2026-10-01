<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\Company;
use App\Models\InwardRegisterDetail;
use App\Models\Station;
use App\Models\Truck;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class LoadingReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view loading_reports', only: ['create', 'print']),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('LoadingReports/Create', [
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'bilties' => $this->pendingBilties(),
            'inward_details' => $this->pendingInwardDetails(),
        ]);
    }

    /**
     * Loading reports are not stored; the print is built from the submitted form.
     */
    public function print(Request $request): View
    {
        $validated = $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'truck_id' => 'required|exists:trucks,id',
            'driver_name' => 'required|string|max:255',
            'date' => 'required|date',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.bilty_id' => 'nullable|integer',
            'details.*.inward_register_detail_id' => 'nullable|integer',
        ]);

        $details = collect($validated['details']);
        $biltyIds = $details->pluck('bilty_id')->filter()->unique()->values();
        $inwardIds = $details->pluck('inward_register_detail_id')->filter()->unique()->values();

        $bilties = Bilty::query()
            ->with(['biltyDetails.good', 'consignee', 'toStation'])
            ->whereIn('id', $biltyIds)
            ->get();

        $inwardDetails = InwardRegisterDetail::query()
            ->with(['good', 'consignee', 'toStation'])
            ->whereIn('id', $inwardIds)
            ->where('disposition', 'forward')
            ->get();

        if ($bilties->isEmpty() && $inwardDetails->isEmpty()) {
            throw ValidationException::withMessages([
                'details' => 'Add at least one bilty or inward GR.',
            ]);
        }

        $report = [
            'date' => $validated['date'],
            'to_station' => Station::query()->whereKey($validated['to_station_id'])->value('name'),
            'truck_number' => Truck::query()->whereKey($validated['truck_id'])->value('truck_number'),
            'driver_name' => $validated['driver_name'],
            'remark' => $validated['remark'] ?? null,
        ];

        $company = Company::find(app(TenantContext::class)->activeCompanyId() ?? $request->user()?->company_id);
        $printData = $this->buildPrintData($this->printRows($bilties, $inwardDetails));

        return view('prints.loading_report', compact('report', 'company', 'printData'));
    }

    private function pendingBilties(): Collection
    {
        return Bilty::with(['biltyDetails.good', 'consignee', 'toStation'])
            ->whereNotIn('status', ['Delivered', 'Cancelled'])
            ->get();
    }

    private function pendingInwardDetails(): Collection
    {
        return InwardRegisterDetail::with(['inwardRegister', 'toStation', 'consignee', 'good'])
            ->where('disposition', 'forward')
            ->whereNotNull('to_station_id')
            ->where('status', 'pending')
            ->get();
    }

    /**
     * @param  Collection<int, Bilty>  $bilties
     * @param  Collection<int, InwardRegisterDetail>  $inwardDetails
     * @return Collection<int, array<string, mixed>>
     */
    private function printRows(Collection $bilties, Collection $inwardDetails): Collection
    {
        $biltyRows = $bilties->map(fn (Bilty $bilty) => [
            'to_station_id' => $bilty->toStation?->id ?? 0,
            'to_station' => $bilty->toStation?->name ?? '—',
            'bilty_number' => $bilty->bilty_number ?? '—',
            'private_marka' => $bilty->private_marka ?? '',
            'nugs' => (float) $bilty->biltyDetails->sum('no_of_packages'),
            'goods_name' => $bilty->biltyDetails
                ->map(fn ($biltyDetail) => $biltyDetail->good?->name)
                ->filter()
                ->unique()
                ->implode(', ') ?: '—',
            'consignee_name' => $bilty->consignee?->name ?? '—',
            'weight' => (float) $bilty->biltyDetails->sum('gross_weight'),
        ]);

        $inwardRows = $inwardDetails->map(fn (InwardRegisterDetail $inward) => [
            'to_station_id' => $inward->toStation?->id ?? 0,
            'to_station' => $inward->toStation?->name ?? '—',
            'bilty_number' => $inward->gr_no ?: 'GR-'.$inward->id,
            'private_marka' => $inward->private_marka ?? '',
            'nugs' => (float) ($inward->no_of_packages ?? 0),
            'goods_name' => $inward->good?->name ?? '—',
            'consignee_name' => $inward->consignee?->name ?? '—',
            'weight' => (float) ($inward->weight ?? 0),
        ]);

        return $biltyRows->concat($inwardRows)->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{groups: Collection<int, array<string, mixed>>, grand_total: array{nugs: float, weight: float}}
     */
    private function buildPrintData(Collection $rows): array
    {
        $groups = $rows
            ->groupBy('to_station_id')
            ->sortBy(fn ($group) => $group->first()['to_station'] ?? '')
            ->map(function ($groupRows) {
                $items = $groupRows->values()->map(function ($row, $index) {
                    $row['serial'] = $index + 1;

                    return $row;
                });

                return [
                    'station_name' => $groupRows->first()['to_station'] ?? '—',
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
