<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\Challan;
use App\Models\ChallanDetail;
use App\Models\InwardRegisterDetail;
use App\Models\Station;
use App\Models\Driver;
use App\Models\Truck;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            'challans' => $challans,
        ]);
    }

    public function create()
    {
        $initial = $this->branchSetting('challan_initial', 'CHL-');
        $lastChallan = Challan::orderBy('id', 'desc')->first();
        $nextId = $lastChallan ? $lastChallan->id + 1 : 1;
        $nextNumber = $initial.str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return Inertia::render('Challans/Create', [
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'bilties' => $this->pendingBilties(),
            'inward_details' => $this->pendingInwardDetails(),
            'agents' => Agent::orderBy('name')->get(['id', 'name']),
            'next_challan_number' => $nextNumber,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate($this->challanRules()));

        $details = $validated['details'] ?? [];
        unset($validated['details']);

        $this->validateChallanDetails($details);
        $this->syncChallanAgentFields($validated);

        DB::transaction(function () use ($validated, $details) {
            $challan = Challan::create($validated);
            $this->syncChallanDetails($challan, $details);
        });

        return redirect()->route('challans.index')->with('success', 'Challan created successfully.');
    }

    public function edit(Challan $challan)
    {
        $challan->load([
            'details.bilty.biltyDetails.good',
            'details.bilty.consignee',
            'details.inwardRegisterDetail.good',
            'details.inwardRegisterDetail.consignee',
            'details.inwardRegisterDetail.toStation',
            'details.toStation',
        ]);

        return Inertia::render('Challans/Edit', [
            'challan' => $challan,
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'bilties' => $this->pendingBilties(),
            'inward_details' => $this->pendingInwardDetails($challan->id),
            'agents' => Agent::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Challan $challan)
    {
        $validated = $this->stripTenantFields($request->validate($this->challanRules()));

        $details = $validated['details'] ?? [];
        unset($validated['details']);

        $this->validateChallanDetails($details);
        $this->syncChallanAgentFields($validated);

        DB::transaction(function () use ($validated, $details, $challan) {
            $challan->update($validated);
            $this->syncChallanDetails($challan, $details);
        });

        return redirect()->route('challans.index')->with('success', 'Challan updated successfully.');
    }

    public function destroy(Challan $challan)
    {
        DB::transaction(function () use ($challan) {
            $inwardIds = $challan->details()
                ->whereNotNull('inward_register_detail_id')
                ->pluck('inward_register_detail_id');

            $biltyIds = $challan->details()
                ->whereNotNull('bilty_id')
                ->pluck('bilty_id');

            InwardRegisterDetail::whereIn('id', $inwardIds)->update(['status' => 'pending']);
            Bilty::whereIn('id', $biltyIds)->update(['status' => 'Pending']);
            $challan->delete();
        });

        return redirect()->route('challans.index')->with('success', 'Challan deleted successfully.');
    }

    public function print(Challan $challan)
    {
        $challan->load([
            'fromStation',
            'toStation',
            'truck',
            'driver',
            'details.bilty.biltyDetails.good',
            'details.bilty.consignee',
            'details.bilty.toStation',
            'details.inwardRegisterDetail.good',
            'details.inwardRegisterDetail.consignee',
            'details.inwardRegisterDetail.toStation',
            'details.toStation',
        ]);
        $company = \App\Models\Company::find($challan->company_id);

        return view('prints.challan', compact('challan', 'company'));
    }

    private function challanRules(): array
    {
        return [
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
            'pay_by_agent_id' => 'nullable|exists:agents,id',
            'delivery_at_agent_id' => 'nullable|exists:agents,id',
            'total_weight' => 'nullable|numeric',
            'total_packages' => 'nullable|numeric',
            'to_pay_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'crossing_freight' => 'nullable|numeric',
            'booking_freight' => 'nullable|numeric',
            'cartage_dalali_pf' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'status' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.row_type' => 'nullable|in:bilty,inward',
            'details.*.bilty_id' => 'nullable|exists:bilties,id',
            'details.*.inward_register_detail_id' => 'nullable|exists:inward_register_details,id',
        ];
    }

    private function validateChallanDetails(array $details): void
    {
        foreach ($details as $index => $detail) {
            $hasBilty = ! empty($detail['bilty_id']);
            $hasInward = ! empty($detail['inward_register_detail_id']);

            if ($hasBilty === $hasInward) {
                throw ValidationException::withMessages([
                    "details.{$index}" => 'Each row must reference either a bilty or an inward register entry.',
                ]);
            }
        }
    }

    private function pendingBilties()
    {
        return Bilty::with(['biltyDetails.good', 'consignee'])
            ->whereNotIn('status', ['Delivered', 'Cancelled', 'In Transit'])
            ->get();
    }

    private function pendingInwardDetails(?int $challanId = null)
    {
        $query = InwardRegisterDetail::with(['inwardRegister', 'toStation', 'consignee', 'good'])
            ->where('disposition', 'forward')
            ->whereNotNull('to_station_id');

        if ($challanId) {
            $linkedIds = ChallanDetail::where('challan_id', $challanId)
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

    private function syncChallanDetails(Challan $challan, array $details): void
    {
        $existingInwardIds = $challan->details()
            ->whereNotNull('inward_register_detail_id')
            ->pluck('inward_register_detail_id');

        $existingBiltyIds = $challan->details()
            ->whereNotNull('bilty_id')
            ->pluck('bilty_id');

        if ($existingInwardIds->isNotEmpty()) {
            InwardRegisterDetail::whereIn('id', $existingInwardIds)->update(['status' => 'pending']);
        }

        if ($existingBiltyIds->isNotEmpty()) {
            Bilty::whereIn('id', $existingBiltyIds)->update(['status' => 'Pending']);
        }

        $challan->details()->delete();

        $bookingFreight = 0.0;
        $crossingFreight = 0.0;

        foreach ($details as $detail) {
            if (! empty($detail['bilty_id'])) {
                $bilty = Bilty::find($detail['bilty_id']);
                if (! $bilty) {
                    continue;
                }

                $challan->details()->create([
                    'bilty_id' => $bilty->id,
                    'to_station_id' => $bilty->to_station_id,
                ]);

                $bilty->update(['status' => 'In Transit']);
                $bookingFreight += (float) ($bilty->freight ?? 0);

                continue;
            }

            if (! empty($detail['inward_register_detail_id'])) {
                $inwardDetail = InwardRegisterDetail::find($detail['inward_register_detail_id']);
                if (! $inwardDetail || $inwardDetail->disposition !== 'forward') {
                    continue;
                }

                $challan->details()->create([
                    'inward_register_detail_id' => $inwardDetail->id,
                    'to_station_id' => $inwardDetail->to_station_id,
                ]);

                $inwardDetail->update(['status' => 'challaned']);
                $crossingFreight += (float) ($inwardDetail->freight ?? 0);
            }
        }

        $challan->update([
            'booking_freight' => $bookingFreight,
            'crossing_freight' => $crossingFreight,
        ]);
    }

    private function syncChallanAgentFields(array &$validated): void
    {
        if (! empty($validated['pay_by_agent_id'])) {
            $agent = Agent::find($validated['pay_by_agent_id']);
            $validated['pay_by'] = $agent?->name;
        } else {
            $validated['pay_by_agent_id'] = null;
            $validated['pay_by'] = null;
        }

        if (! empty($validated['delivery_at_agent_id'])) {
            $agent = Agent::find($validated['delivery_at_agent_id']);
            $validated['delivery_at'] = $agent?->name;
        } else {
            $validated['delivery_at_agent_id'] = null;
            $validated['delivery_at'] = null;
        }
    }
}
