<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AgentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view agents', only: ['index', 'show']),
            new Middleware('permission:create agents', only: ['create', 'store']),
            new Middleware('permission:edit agents', only: ['edit', 'update']),
            new Middleware('permission:delete agents', only: ['destroy']),
        ];
    }

    public function index()
    {
        $agents = Agent::with('company')->latest()->paginate(10);

        return Inertia::render('Agents/Index', [
            'agents' => $agents,
        ]);
    }

    public function create()
    {
        return Inertia::render('Agents/Create', [
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|max:255',
            'mobile_sms' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'gst_no' => 'nullable|string|max:255',
        ]));

        Agent::create($validated);

        return redirect()->route('agents.index')->with('success', 'Agent created successfully.');
    }

    public function edit(Agent $agent)
    {
        return Inertia::render('Agents/Edit', [
            'agent' => $agent,
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|max:255',
            'mobile_sms' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'gst_no' => 'nullable|string|max:255',
        ]));

        $agent->update($validated);

        return redirect()->route('agents.index')->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();

        return redirect()->route('agents.index')->with('success', 'Agent deleted successfully.');
    }
}
