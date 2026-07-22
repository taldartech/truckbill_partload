<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class StationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view stations', only: ['index', 'show', 'quickShow']),
            new Middleware('permission:create stations', only: ['create', 'store', 'quickStore', 'quickFormData']),
            new Middleware('permission:edit stations', only: ['edit', 'update']),
            new Middleware('permission:delete stations', only: ['destroy']),
        ];
    }

    public function index()
    {
        $stations = Station::with('city', 'state')->latest()->paginate(10);

        return Inertia::render('Stations/Index', [
            'stations' => $stations,
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'state_id']),
        ]);
    }

    public function create()
    {
        return redirect()->route('stations.index');
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
        ]));

        Station::create($validated);

        return redirect()->back()->with('success', 'Station created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        return response()->json([
            'states' => State::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function quickShow(Station $station): JsonResponse
    {
        $station->load(['state', 'city']);

        return response()->json([
            'rows' => [
                ['label' => 'Name', 'value' => $station->name],
                ['label' => 'State', 'value' => $station->state?->name],
                ['label' => 'City', 'value' => $station->city?->name],
            ],
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
        ]));

        $station = Station::create($validated);
        $countryId = State::query()
            ->whereKey($station->state_id)
            ->value('country_id');

        return response()->json([
            'station' => [
                'id' => $station->id,
                'name' => $station->name,
                'country_id' => $countryId,
                'state_id' => $station->state_id,
                'city_id' => $station->city_id,
            ],
        ], 201);
    }

    public function edit(Station $station)
    {
        return redirect()->route('stations.index');
    }

    public function update(Request $request, Station $station)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
        ]));

        $station->update($validated);

        return redirect()->back()->with('success', 'Station updated successfully.');
    }

    public function destroy(Station $station)
    {
        $station->delete();

        return redirect()->back()->with('success', 'Station deleted successfully.');
    }
}
