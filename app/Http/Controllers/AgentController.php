<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Company;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::with('company')->latest()->paginate(10);
        return Inertia::render('Agents/Index', [
            'agents' => $agents
        ]);
    }

    public function create()
    {
        return Inertia::render('Agents/Create', [
            'companies' => Company::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|max:255',
            'mobile_sms' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'gst_no' => 'nullable|string|max:255'
        ]);

        Agent::create($validated);
        return redirect()->route('agents.index')->with('success', 'Agent created successfully.');
    }

    public function edit(Agent $agent)
    {
        return Inertia::render('Agents/Edit', [
            'agent' => $agent,
            'companies' => Company::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|max:255',
            'mobile_sms' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'gst_no' => 'nullable|string|max:255'
        ]);

        $agent->update($validated);
        return redirect()->route('agents.index')->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();
        return redirect()->route('agents.index')->with('success', 'Agent deleted successfully.');
    }
}
