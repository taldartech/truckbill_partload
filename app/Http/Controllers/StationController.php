<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Company;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class StationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view stations', only: ['index', 'show']),
            new Middleware('permission:create stations', only: ['create', 'store']),
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