<?php

namespace App\Http\Controllers;

use App\Models\Challan;
use App\Models\Company;
use App\Models\MainChallan;
use App\Models\Station;
use App\Models\Truck;
use App\Support\DocumentNumber;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
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

        $mainChallans = MainChallan::query()
            ->with(['company', 'fromStation', 'toStation', 'truck'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('main_challan_number', 'like', '%'.$search.'%')
                        ->orWhere('driver_name', 'like', '%'.$search.'%')
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

        return Inertia::render('MainChallans/Index', [
            'main_challans' => $mainChallans,
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
        $branch = $this->activeBranch();
        $nextNumber = DocumentNumber::next(
            MainChallan::query(),
            'main_challan_number',
            $branch?->main_challan_initial
        );

        return Inertia::render('MainChallans/Create', [
            'stations' => Station::all(),
            'trucks' => Truck::all(),
            'challans' => $this->pendingChallans(),
            'next_main_challan_number' => $nextNumber,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'driver_name' => 'required|string|max:255',
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
        $validated['created_by'] = Auth::id();

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
            'trucks' => Truck::all(),
            'challans' => $this->pendingChallans(),
        ]);
    }

    public function update(Request $request, MainChallan $main_challan)
    {
        $validated = $this->stripTenantFields($request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id',
            'driver_name' => 'required|string|max:255',
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
            'creator',
            'branch.company',
            'branch.city',
            'branch.state',
            'details.challan.toStation',
            'details.challan.details.bilty.biltyDetails',
            'details.challan.details.inwardRegisterDetail',
        ]);

        $branch = $main_challan->branch;
        $company = $branch?->company ?? Company::find($main_challan->company_id);

        return view('prints.main_challan', compact('main_challan', 'company', 'branch'));
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
}
