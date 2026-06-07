<?php

namespace App\Http\Controllers;

use App\Models\InwardRegister;
use App\Models\Station;
use App\Models\Driver;
use App\Models\Truck;
use App\Models\Good;
use App\Models\Party;
use App\Models\Transporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InwardRegisterController extends Controller
{
    public function index()
    {
        $registers = InwardRegister::with(['fromStation', 'toStation', 'truck', 'driver', 'details'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('InwardRegisters/Index', [
            'registers' => $registers,
        ]);
    }

    public function create()
    {
        return Inertia::render('InwardRegisters/Create', [
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'goods' => Good::all(),
            'parties' => Party::all(),
            'transporters' => Transporter::all(),
            'next_inward_register_no' => $this->nextInwardRegisterNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInwardRegister($request);

        DB::transaction(function () use ($validated) {
            $details = $validated['details'];
            unset($validated['details']);

            $this->applyDetailTotals($validated, $details);

            $validated['company_id'] = $validated['company_id']
                ?? (auth()->user()?->company_id ?: 1);

            $register = InwardRegister::create($validated);
            $this->syncDetails($register, $details);
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
            'details.transporter',
        ]);

        return Inertia::render('InwardRegisters/Edit', [
            'register' => $inwardRegister,
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'goods' => Good::all(),
            'parties' => Party::all(),
            'transporters' => Transporter::all(),
        ]);
    }

    public function update(Request $request, InwardRegister $inwardRegister)
    {
        $validated = $this->validateInwardRegister($request, $inwardRegister->id);

        DB::transaction(function () use ($validated, $inwardRegister) {
            $details = $validated['details'];
            unset($validated['details']);

            $this->applyDetailTotals($validated, $details);

            $inwardRegister->update($validated);
            $this->syncDetails($inwardRegister, $details);
        });

        return redirect()->route('inward-registers.index')->with('success', 'Inward Register updated successfully.');
    }

    public function destroy(InwardRegister $inwardRegister)
    {
        $inwardRegister->delete();

        return redirect()->route('inward-registers.index')->with('success', 'Inward Register deleted successfully.');
    }

    private function nextInwardRegisterNumber(): string
    {
        $companyId = auth()->user()?->company_id ?: 1;
        $initial = \App\Models\Setting::where('company_id', $companyId)
            ->where('key', 'inward_register_initial')
            ->value('value') ?? 'IR-';

        $last = InwardRegister::where('company_id', $companyId)
            ->where('inward_register_no', 'like', $initial . '%')
            ->orderByDesc('id')
            ->value('inward_register_no');

        if (!$last) {
            return $initial . '0001';
        }

        $sequence = (int) preg_replace('/\D/', '', substr($last, strlen($initial)));

        return $initial . str_pad($sequence + 1, 4, '0', STR_PAD_LEFT);
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
            'driver_id' => 'nullable|exists:drivers,id',
            'truck_id' => 'nullable|exists:trucks,id',
            'total_packages' => 'nullable|integer|min:0',
            'total_delivery_freight' => 'nullable|numeric|min:0',
            'total_crossing_freight' => 'nullable|numeric|min:0',
            'details' => 'required|array|min:1',
            ...$detailIdRule,
            'details.*.gr_no' => 'nullable|string|max:255',
            'details.*.date' => 'nullable|date',
            'details.*.from_station_id' => 'nullable|exists:stations,id',
            'details.*.to_station_id' => 'nullable|exists:stations,id',
            'details.*.consignor_id' => 'nullable|exists:parties,id',
            'details.*.consignee_id' => 'nullable|exists:parties,id',
            'details.*.good_id' => 'nullable|exists:goods,id',
            'details.*.no_of_packages' => 'nullable|integer|min:0',
            'details.*.weight' => 'nullable|numeric|min:0',
            'details.*.basis_of_booking' => 'nullable|string|max:50',
            'details.*.freight' => 'nullable|numeric|min:0',
            'details.*.home_delivery_charge' => 'nullable|numeric|min:0',
            'details.*.private_marka' => 'nullable|string|max:255',
            'details.*.transporter_id' => 'nullable|exists:transporters,id',
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
            'transporter_id' => $detail['transporter_id'] ?? null,
        ];
    }

    private function applyDetailTotals(array &$validated, array $details): void
    {
        $validated['total_packages'] = collect($details)->sum(fn ($row) => (int) ($row['no_of_packages'] ?? 0));
        $validated['total_delivery_freight'] = collect($details)->sum(fn ($row) => (float) ($row['home_delivery_charge'] ?? 0));
        $validated['total_crossing_freight'] = collect($details)->sum(fn ($row) => (float) ($row['freight'] ?? 0));
    }

    private function syncDetails(InwardRegister $register, array $details): void
    {
        $keptIds = [];

        foreach ($details as $detail) {
            $payload = [
                ...$this->normalizeDetail($detail),
                'company_id' => $register->company_id,
            ];

            if (!empty($detail['id'])) {
                $existing = $register->details()->whereKey($detail['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;
                    continue;
                }
            }

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
