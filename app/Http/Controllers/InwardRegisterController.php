<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Good;
use App\Models\InwardRegister;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use App\Services\Accounting\AccountingPoster;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InwardRegisterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view inward_registers', only: ['index', 'show']),
            new Middleware('permission:create inward_registers', only: ['create', 'store']),
            new Middleware('permission:edit inward_registers', only: ['edit', 'update']),
            new Middleware('permission:delete inward_registers', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'from_station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'truck_id' => ['nullable', 'integer', 'exists:trucks,id'],
        ]);

        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->endOfMonth()->toDateString();
        $search = trim((string) ($validated['search'] ?? ''));

        $registers = InwardRegister::query()
            ->with(['fromStation', 'toStation', 'truck', 'details'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('inward_register_no', 'like', '%'.$search.'%')
                        ->orWhere('challan_number', 'like', '%'.$search.'%')
                        ->orWhere('driver_name', 'like', '%'.$search.'%')
                        ->orWhereHas('truck', function ($truckQuery) use ($search) {
                            $truckQuery->where('truck_number', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($fromDate, fn ($query) => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('date', '<=', $toDate))
            ->when(! empty($validated['from_station_id']), function ($query) use ($validated) {
                $query->where('from_station_id', $validated['from_station_id']);
            })
            ->when(! empty($validated['truck_id']), function ($query) use ($validated) {
                $query->where('truck_id', $validated['truck_id']);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('InwardRegisters/Index', [
            'registers' => $registers,
            'stations' => Station::query()->orderBy('name')->get(['id', 'name']),
            'trucks' => Truck::query()
                ->orderBy('truck_number')
                ->get(['id', 'truck_number'])
                ->map(fn (Truck $truck) => [
                    'id' => $truck->id,
                    'name' => $truck->truck_number,
                ]),
            'filters' => [
                'search' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'from_station_id' => isset($validated['from_station_id']) ? (string) $validated['from_station_id'] : '',
                'truck_id' => isset($validated['truck_id']) ? (string) $validated['truck_id'] : '',
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('InwardRegisters/Create', [
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'goods' => Good::all(),
            'parties' => Party::all(),
            'agents' => Agent::orderBy('name')->get(['id', 'name']),
            'next_inward_register_no' => $this->nextInwardRegisterNumber(),
        ]);
    }

    public function store(Request $request, AccountingPoster $poster)
    {
        $this->branchStationId();
        $validated = $this->validateInwardRegister($request);

        DB::transaction(function () use ($validated, $poster) {
            $details = $validated['details'];
            unset($validated['details']);

            $this->applyDetailTotals($validated, $details);

            $register = InwardRegister::create($validated);
            $this->syncDetails($register, $details);
            $poster->syncInwardRegister($register->fresh());
        });

        return redirect()->route('inward-registers.index')->with('success', 'Inward Register created successfully.');
    }

    public function edit(InwardRegister $inwardRegister)
    {
        $inwardRegister->load([
            'details.fromStation',
            'details.toStation',
            'details.consignor',
            'details.consignee',
            'details.good',
            'details.agent',
        ]);

        return Inertia::render('InwardRegisters/Edit', [
            'register' => $inwardRegister,
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'goods' => Good::all(),
            'parties' => Party::all(),
            'agents' => Agent::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, InwardRegister $inwardRegister, AccountingPoster $poster)
    {
        $this->branchStationId();
        $validated = $this->validateInwardRegister($request, $inwardRegister->id);

        DB::transaction(function () use ($validated, $inwardRegister, $poster) {
            $details = $validated['details'];
            unset($validated['details']);

            $this->applyDetailTotals($validated, $details);

            $inwardRegister->update($validated);
            $this->syncDetails($inwardRegister, $details);
            $poster->syncInwardRegister($inwardRegister->fresh());
        });

        return redirect()->route('inward-registers.index')->with('success', 'Inward Register updated successfully.');
    }

    public function destroy(InwardRegister $inwardRegister, AccountingPoster $poster)
    {
        DB::transaction(function () use ($inwardRegister, $poster) {
            $poster->clearReference($inwardRegister);
            $inwardRegister->delete();
        });

        return redirect()->route('inward-registers.index')->with('success', 'Inward Register deleted successfully.');
    }

    private function branchStationId(): int
    {
        $branchStationId = $this->tenant()->activeBranch()?->station_id;

        if (! $branchStationId) {
            abort(422, 'Branch station is not configured. Please update the branch with a station before saving inward registers.');
        }

        return (int) $branchStationId;
    }

    private function resolveDisposition(int $toStationId): string
    {
        return $toStationId === $this->branchStationId() ? 'delivery' : 'forward';
    }

    private function nextInwardRegisterNumber(): string
    {
        $branch = $this->activeBranch();

        return \App\Support\DocumentNumber::next(
            InwardRegister::query(),
            'inward_register_no',
            $branch?->inward_register_initial
        );
    }

    private function validateInwardRegister(Request $request, ?int $inwardRegisterId = null): array
    {
        $detailIdRule = $inwardRegisterId
            ? [
                'details.*.id' => [
                    'nullable',
                    'integer',
                    Rule::exists('inward_register_details', 'id')->where(
                        fn ($query) => $query->where('inward_register_id', $inwardRegisterId)
                    ),
                ],
            ]
            : [];

        return $request->validate([
            'inward_register_no' => 'nullable|string|max:255',
            'date' => 'required|date',
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'challan_number' => 'nullable|string|max:255',
            'challan_date' => 'nullable|date',
            'driver_name' => 'nullable|string|max:255',
            'truck_id' => 'nullable|exists:trucks,id',
            'total_packages' => 'nullable|integer|min:0',
            'total_delivery_freight' => 'nullable|numeric|min:0',
            'total_crossing_freight' => 'nullable|numeric|min:0',
            'details' => 'required|array|min:1',
            ...$detailIdRule,
            'details.*.gr_no' => 'nullable|string|max:255',
            'details.*.date' => 'nullable|date',
            'details.*.from_station_id' => 'nullable|exists:stations,id',
            'details.*.to_station_id' => 'required|exists:stations,id',
            'details.*.consignor_id' => 'nullable|exists:parties,id',
            'details.*.consignee_id' => 'nullable|exists:parties,id',
            'details.*.good_id' => 'nullable|exists:goods,id',
            'details.*.no_of_packages' => 'nullable|integer|min:0',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.basis_of_booking' => 'nullable|string|max:50',
            'details.*.freight' => 'nullable|numeric|min:0',
            'details.*.home_delivery_charge' => 'nullable|numeric|min:0',
            'details.*.private_marka' => 'nullable|string|max:255',
            'details.*.agent_id' => 'nullable|exists:agents,id',
        ]);
    }

    private function normalizeDetail(array $detail): array
    {
        return [
            'gr_no' => $detail['gr_no'] ?? null,
            'date' => $detail['date'] ?? null,
            'from_station_id' => $detail['from_station_id'] ?? null,
            'to_station_id' => $detail['to_station_id'] ?? null,
            'consignor_id' => $detail['consignor_id'] ?? null,
            'consignee_id' => $detail['consignee_id'] ?? null,
            'good_id' => $detail['good_id'] ?? null,
            'no_of_packages' => $detail['no_of_packages'] ?? null,
            'weight' => $detail['weight'] ?? null,
            'basis_of_booking' => $detail['basis_of_booking'] ?? null,
            'freight' => $detail['freight'] ?? null,
            'home_delivery_charge' => $detail['home_delivery_charge'] ?? null,
            'private_marka' => $detail['private_marka'] ?? null,
            'agent_id' => $detail['agent_id'] ?? null,
        ];
    }

    private function applyDetailTotals(array &$validated, array $details): void
    {
        $rows = collect($details);

        $validated['total_packages'] = $rows->sum(fn ($row) => (int) ($row['no_of_packages'] ?? 0));

        $validated['total_delivery_freight'] = $rows
            ->filter(fn ($row) => $this->resolveDisposition((int) ($row['to_station_id'] ?? 0)) === 'delivery')
            ->sum(fn ($row) => (float) ($row['freight'] ?? 0));

        $validated['total_crossing_freight'] = $rows
            ->filter(fn ($row) => $this->resolveDisposition((int) ($row['to_station_id'] ?? 0)) === 'forward')
            ->sum(fn ($row) => (float) ($row['freight'] ?? 0));
    }

    private function syncDetails(InwardRegister $register, array $details): void
    {
        $keptIds = [];

        foreach ($details as $detail) {
            $payload = $this->normalizeDetail($detail);
            $toStationId = (int) ($payload['to_station_id'] ?? 0);

            if (! empty($detail['id'])) {
                $existing = $register->details()->whereKey($detail['id'])->first();
                if ($existing) {
                    if ($existing->status === 'challaned') {
                        unset($payload['to_station_id']);
                    } else {
                        $payload['disposition'] = $this->resolveDisposition($toStationId);
                    }

                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $payload['disposition'] = $this->resolveDisposition($toStationId);
            $payload['status'] = 'pending';

            $created = $register->details()->create($payload);
            $keptIds[] = $created->id;
        }

        if (empty($keptIds)) {
            $register->details()->delete();
        } else {
            $register->details()->whereNotIn('id', $keptIds)->delete();
        }
    }
}
