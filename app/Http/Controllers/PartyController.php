<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Company;
use App\Models\Group;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class PartyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view parties', only: ['index', 'show']),
            new Middleware('permission:create parties', only: ['create', 'store']),
            new Middleware('permission:edit parties', only: ['edit', 'update']),
            new Middleware('permission:delete parties', only: ['destroy']),
        ];
    }

    public function index()
    {
        $parties = Party::with('company')->latest()->paginate(10);
        return Inertia::render('Parties/Index', [
            'parties' => $parties
        ]);
    }

    public function create()
    {
        return Inertia::render('Parties/Create', [
            'companies' => Company::all(),
            'groups' => Group::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'nullable|string',
            'type' => 'nullable|string',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string',
            'mobile_sms' => 'nullable|string',
            'email' => 'nullable|email',
            'gst_number' => 'nullable|string',
            'bilty_charge' => 'nullable|numeric'
        ]);

        Party::create($validated);
        return redirect()->route('parties.index')->with('success', 'Party created successfully.');
    }

    public function edit(Party $party)
    {
        return Inertia::render('Parties/Edit', [
            'party' => $party,
            'companies' => Company::all(),
            'groups' => Group::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function update(Request $request, Party $party)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'nullable|string',
            'type' => 'nullable|string',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string',
            'mobile_sms' => 'nullable|string',
            'email' => 'nullable|email',
            'gst_number' => 'nullable|string',
            'bilty_charge' => 'nullable|numeric'
        ]);

        $party->update($validated);
        return redirect()->route('parties.index')->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party)
    {
        $party->delete();
        return redirect()->route('parties.index')->with('success', 'Party deleted successfully.');
    }
}