<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\City;
use App\Models\State;
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
        ]);
    }

    public function create()
    {
        return Inertia::render('Stations/Create', [
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
        ]));

        Station::create($validated);

        return redirect()->route('stations.index')->with('success', 'Station created successfully.');
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
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
        ]));

        $station = Station::create($validated);

        return response()->json([
            'station' => [
                'id' => $station->id,
                'name' => $station->name,
            ],
        ], 201);
    }

    public function edit(Station $station)
    {
        return Inertia::render('Stations/Edit', [
            'station' => $station,
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function update(Request $request, Station $station)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
        ]));

        $station->update($validated);

        return redirect()->route('stations.index')->with('success', 'Station updated successfully.');
    }

    public function destroy(Station $station)
    {
        $station->delete();

        return redirect()->route('stations.index')->with('success', 'Station deleted successfully.');
    }
}
