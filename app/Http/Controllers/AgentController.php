<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;
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
            new Middleware('permission:create agents', only: ['create', 'store', 'quickStore', 'quickFormData']),
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
        $validated = $this->stripTenantFields($request->validate(
            $this->agentRules(),
            $this->agentMessages(),
        ));

        Agent::create($validated);

        return redirect()->route('agents.index')->with('success', 'Agent created successfully.');
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
            $this->agentRules(),
            $this->agentMessages(),
        ));

        $agent = Agent::create($validated);

        return response()->json([
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
            ],
        ], 201);
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
        $validated = $this->stripTenantFields($request->validate(
            $this->agentRules(),
            $this->agentMessages(),
        ));

        $agent->update($validated);

        return redirect()->route('agents.index')->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();

        return redirect()->route('agents.index')->with('success', 'Agent deleted successfully.');
    }

    /**
     * @return array<string, string>
     */
    private function agentRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'mobile' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_no' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function agentMessages(): array
    {
        return [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'gst_no.size' => 'GST number must be exactly 15 characters.',
            'gst_no.regex' => 'GST number format is invalid.',
        ];
    }
}
