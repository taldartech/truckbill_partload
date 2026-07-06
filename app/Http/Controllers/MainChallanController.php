<?php

namespace App\Http\Controllers;

use App\Models\MainChallan;
use App\Models\Challan;
use App\Models\Station;
use App\Models\Driver;
use App\Models\Truck;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MainChallanController extends Controller implements HasMiddleware
{
    use RedirectsWithOptionalPrint;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view challans', only: ['index', 'show', 'print']),
            new Middleware('permission:create challans', only: ['create', 'store']),
            new Middleware('permission:edit challans', only: ['edit', 'update']),
            new Middleware('permission:delete challans', only: ['destroy']),
        ];
    }

    public function index()
    {
        $main_challans = MainChallan::with(['company', 'fromStation', 'toStation', 'driver', 'truck'])->latest()->paginate(10);
        return Inertia::render('MainChallans/Index', [
            'main_challans' => $main_challans
        ]);
    }

    public function create()
    {
        $branch = $this->activeBranch();
        $nextNumber = \App\Support\DocumentNumber::next(
            MainChallan::query(),
            'main_challan_number',
            $branch?->main_challan_initial
        );

        return Inertia::render('MainChallans/Create', [
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'challans' => $this->pendingChallans(),
            'next_main_challan_number' => $nextNumber
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'main_challan_number' => 'nullable|string',
            'date' => 'nullable|date',
            'labour_charge' => 'nullable|numeric',
            'kat' => 'nullable|numeric',
            'home_delivery_charge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'truck_freight' => 'nullable|numeric',
            'cash_received' => 'nullable|numeric',
            'cash_paid' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'total_balance_type' => 'nullable|string',
            'pay_by' => 'nullable|string',
            'total_weight' => 'nullable|numeric',
            'total_packages' => 'nullable|numeric',
            'to_pay_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'crossing_freight' => 'nullable|numeric',
            'cartage_dalali_pf' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'status' => 'nullable|string',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.challan_id' => 'required|exists:challans,id',
        ]));

        $details = $validated['details'] ?? [];
        unset($validated['details']);

        $mainChallan = DB::transaction(function () use ($validated, $details) {
            $mainChallan = MainChallan::create($validated);
            $this->syncMainChallanDetails($mainChallan, $details);

            return $mainChallan;
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'main-challans.index',
            'main-challans.print',
            $mainChallan,
            'Main Challan created successfully.',
        );
    }

    public function edit(MainChallan $main_challan)
    {
        $main_challan->load([
            'details.challan.details.bilty.biltyDetails.good',
            'details.challan.details.inwardRegisterDetail',
            'details.challan.toStation',
        ]);

        return Inertia::render('MainChallans/Edit', [
            'main_challan' => $main_challan,
            'stations' => Station::all(),
            'drivers' => Driver::all(),
            'trucks' => Truck::all(),
            'challans' => $this->pendingChallans(),
        ]);
    }

    public function update(Request $request, MainChallan $main_challan)
    {
        $validated = $this->stripTenantFields($request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'driver_id' => 'required|exists:drivers,id',
            'truck_id' => 'required|exists:trucks,id',
            'main_challan_number' => 'nullable|string',
            'date' => 'nullable|date',
            'labour_charge' => 'nullable|numeric',
            'kat' => 'nullable|numeric',
            'home_delivery_charge' => 'nullable|numeric',
            'other_charge' => 'nullable|numeric',
            'truck_freight' => 'nullable|numeric',
            'cash_received' => 'nullable|numeric',
            'cash_paid' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'total_balance_type' => 'nullable|string',
            'pay_by' => 'nullable|string',
            'total_weight' => 'nullable|numeric',
            'total_packages' => 'nullable|numeric',
            'to_pay_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'crossing_freight' => 'nullable|numeric',
            'cartage_dalali_pf' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'status' => 'nullable|string',
            'remark' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.challan_id' => 'required|exists:challans,id',
        ]));

        $details = $validated['details'] ?? [];
        unset($validated['details']);

        DB::transaction(function () use ($validated, $details, $main_challan) {
            $main_challan->update($validated);
            $this->syncMainChallanDetails($main_challan, $details);
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'main-challans.index',
            'main-challans.print',
            $main_challan,
            'Main Challan updated successfully.',
        );
    }

    public function destroy(MainChallan $main_challan)
    {
        DB::transaction(function () use ($main_challan) {
            $challanIds = $main_challan->details()->pluck('challan_id');

            Challan::whereIn('id', $challanIds)->update(['status' => 'Created']);
            $main_challan->delete();
        });

        return redirect()->route('main-challans.index')->with('success', 'Main Challan deleted successfully.');
    }

    public function print(MainChallan $main_challan)
    {
        $main_challan->load([
            'fromStation',
            'toStation',
            'truck',
            'driver',
            'details.challan.toStation',
            'details.challan.details.bilty.biltyDetails',
            'details.challan.details.inwardRegisterDetail',
        ]);

        $company = \App\Models\Company::find($main_challan->company_id);
        $printData = $this->buildPrintData($main_challan);

        return view('prints.main_challan', compact('main_challan', 'company', 'printData'));
    }

    private function pendingChallans()
    {
        return Challan::with([
            'details.bilty.biltyDetails.good',
            'details.inwardRegisterDetail.good',
            'toStation',
        ])
            ->where(function ($query) {
                $query->where('status', 'Created')
                    ->orWhereNull('status');
            })
            ->get();
    }

    private function syncMainChallanDetails(MainChallan $mainChallan, array $details): void
    {
        $existingChallanIds = $mainChallan->details()->pluck('challan_id');

        if ($existingChallanIds->isNotEmpty()) {
            Challan::whereIn('id', $existingChallanIds)->update(['status' => 'Created']);
        }

        $mainChallan->details()->delete();

        foreach ($details as $detail) {
            $challan = Challan::find($detail['challan_id'] ?? null);
            if (! $challan) {
                continue;
            }

            $mainChallan->details()->create([
                'challan_id' => $challan->id,
            ]);

            $challan->update(['status' => 'In Progress']);
        }
    }

    private function buildPrintData(MainChallan $mainChallan): array
    {
        $rows = $mainChallan->details->map(function ($detail) {
            $challan = $detail->challan;
            if (!$challan) {
                return null;
            }

            $totalNugs = (float) ($challan->total_packages ?? 0);
            $totalWeight = (float) ($challan->total_weight ?? 0);

            if ($challan->details) {
                $nugsFromDetails = 0;
                $weightFromDetails = 0;

                foreach ($challan->details as $challanDetail) {
                    $bilty = $challanDetail->bilty;
                    if ($bilty?->biltyDetails) {
                        foreach ($bilty->biltyDetails as $biltyDetail) {
                            $nugsFromDetails += (float) ($biltyDetail->no_of_packages ?? 0);
                            $weightFromDetails += (float) ($biltyDetail->gross_weight ?? 0);
                        }
                    }

                    $inward = $challanDetail->inwardRegisterDetail;
                    if ($inward) {
                        $nugsFromDetails += (float) ($inward->no_of_packages ?? 0);
                        $weightFromDetails += (float) ($inward->weight ?? 0);
                    }
                }

                if ($nugsFromDetails > 0) {
                    $totalNugs = $nugsFromDetails;
                }
                if ($weightFromDetails > 0) {
                    $totalWeight = $weightFromDetails;
                }
            }

            return [
                'challan_number' => $challan->challan_number ?? '—',
                'to_station' => $challan->toStation?->name ?? '—',
                'nugs' => $totalNugs,
                'weight' => $totalWeight,
                'to_pay' => (float) ($challan->to_pay_amount ?? 0),
                'paid_amount' => (float) ($challan->paid_amount ?? 0),
                'crossing' => (float) ($challan->crossing_freight ?? 0),
                'cartage' => (float) ($challan->cartage_dalali_pf ?? 0),
                'labour' => (float) ($challan->labour_charge ?? 0),
                'kat' => (float) ($challan->kat ?? 0),
                'home_delivery' => (float) ($challan->home_delivery_charge ?? 0),
                'truck_freight' => (float) ($challan->truck_freight ?? 0),
                'gst' => (float) ($challan->gst ?? 0),
                'cash_received' => (float) ($challan->cash_received ?? 0),
                'cash_paid' => (float) ($challan->cash_payment ?? 0),
            ];
        })->filter()->values();

        return [
            'rows' => $rows,
            'totals' => [
                'nugs' => $rows->sum('nugs'),
                'weight' => $rows->sum('weight'),
                'to_pay' => $rows->sum('to_pay'),
                'paid_amount' => $rows->sum('paid_amount'),
                'crossing' => $rows->sum('crossing'),
                'cartage' => $rows->sum('cartage'),
                'labour' => $rows->sum('labour'),
                'kat' => $rows->sum('kat'),
                'home_delivery' => $rows->sum('home_delivery'),
                'truck_freight' => $rows->sum('truck_freight'),
                'gst' => $rows->sum('gst'),
                'cash_received' => $rows->sum('cash_received'),
                'cash_paid' => $rows->sum('cash_paid'),
            ],
        ];
    }
}
