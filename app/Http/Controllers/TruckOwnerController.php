<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\TruckOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class TruckOwnerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view truck_owners', only: ['index', 'show']),
            new Middleware('permission:create truck_owners', only: ['create', 'store', 'quickStore', 'quickFormData']),
            new Middleware('permission:edit truck_owners', only: ['edit', 'update']),
            new Middleware('permission:delete truck_owners', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $truckOwners = TruckOwner::query()
            ->with('company')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%')
                        ->orWhere('gst_number', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TruckOwners/Index', [
            'truckOwners' => $truckOwners,
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'state_id']),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(TruckOwner $truckOwner)
    {
        $truckOwner->load(['state', 'city', 'company']);

        return Inertia::render('TruckOwners/Show', [
            'truckOwner' => $truckOwner,
        ]);
    }

    public function create()
    {
        return redirect()->route('truck-owners.index');
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->truckOwnerRules(),
            $this->truckOwnerMessages(),
        ));

        TruckOwner::create($validated);

        return redirect()->back()->with('success', 'Truck owner created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        return response()->json([
            'states' => State::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->truckOwnerRules(),
            $this->truckOwnerMessages(),
        ));

        $truckOwner = TruckOwner::create($validated);

        return response()->json([
            'truck_owner' => [
                'id' => $truckOwner->id,
                'name' => $truckOwner->name,
            ],
        ], 201);
    }

    public function edit(TruckOwner $truckOwner)
    {
        return redirect()->route('truck-owners.index');
    }

    public function update(Request $request, TruckOwner $truckOwner)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->truckOwnerRules(),
            $this->truckOwnerMessages(),
        ));

        $truckOwner->update($validated);

        return redirect()->back()->with('success', 'Truck owner updated successfully.');
    }

    public function destroy(TruckOwner $truckOwner)
    {
        $truckOwner->delete();

        return redirect()->route('truck-owners.index')->with('success', 'Truck owner deleted successfully.');
    }

    /**
     * @return array<string, string>
     */
    private function truckOwnerRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'mobile' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function truckOwnerMessages(): array
    {
        return [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
        ];
    }
}
