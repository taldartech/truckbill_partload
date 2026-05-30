<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Company;
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
        $stations = Station::with('company')->latest()->paginate(10);
        return Inertia::render('Stations/Index', [
            'stations' => $stations
        ]);
    }

    public function create()
    {
        return Inertia::render('Stations/Create', [
            'companies' => Company::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
        ]);

        Station::create($validated);
        return redirect()->route('stations.index')->with('success', 'Station created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        $data = [
            'states' => State::select('id', 'name')->orderBy('name')->get(),
            'default_company_id' => auth()->user()?->company_id,
        ];

        if (auth()->user()?->hasRole(['master_admin', 'super_admin'])) {
            $data['companies'] = Company::select('id', 'name')->orderBy('name')->get();
        }

        return response()->json($data);
    }

    public function quickShow(Station $station): JsonResponse
    {
        $station->load(['state', 'city', 'company']);

        return response()->json([
            'rows' => [
                ['label' => 'Name', 'value' => $station->name],
                ['label' => 'State', 'value' => $station->state?->name],
                ['label' => 'City', 'value' => $station->city?->name],
                ['label' => 'Company', 'value' => $station->company?->name],
            ],
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
        ]);

        if (empty($validated['company_id']) && auth()->user()?->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        }

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
            'companies' => Company::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function update(Request $request, Station $station)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
        ]);

        $station->update($validated);
        return redirect()->route('stations.index')->with('success', 'Station updated successfully.');
    }

    public function destroy(Station $station)
    {
        $station->delete();
        return redirect()->route('stations.index')->with('success', 'Station deleted successfully.');
    }
}