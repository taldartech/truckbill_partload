<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\City;
use App\Models\State;
use App\Services\Accounting\EntityAccountLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
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

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $agents = Agent::query()
            ->with(['company', 'city'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%')
                        ->orWhere('gst_no', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Agents/Index', [
            'agents' => $agents,
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'state_id']),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Agent $agent)
    {
        $agent->load(['state', 'city', 'company']);

        return Inertia::render('Agents/Show', [
            'agent' => $agent,
        ]);
    }

    public function create()
    {
        return redirect()->route('agents.index');
    }

    public function store(Request $request, EntityAccountLinker $linker)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->agentRules(),
            $this->agentMessages(),
        ));

        $accountGroupId = $validated['account_group_id'] ?? null;
        unset($validated['account_group_id']);
        $validated['consider_for_accounting'] = (bool) ($validated['consider_for_accounting'] ?? false);

        $agent = Agent::create($validated);

        if ($agent->consider_for_accounting) {
            $linker->enable($agent, $accountGroupId ? (int) $accountGroupId : null);
        }

        return redirect()->back()->with('success', 'Agent created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        return response()->json([
            'states' => State::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function quickStore(Request $request, EntityAccountLinker $linker): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->agentRules(),
            $this->agentMessages(),
        ));

        unset($validated['account_group_id']);
        $validated['consider_for_accounting'] = (bool) ($validated['consider_for_accounting'] ?? false);

        $agent = Agent::create($validated);

        if ($agent->consider_for_accounting) {
            $linker->enable($agent);
        }

        return response()->json([
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
            ],
        ], 201);
    }

    public function edit(Agent $agent)
    {
        return redirect()->route('agents.index');
    }

    public function update(Request $request, Agent $agent, EntityAccountLinker $linker)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->agentRules(),
            $this->agentMessages(),
        ));

        $accountGroupId = $validated['account_group_id'] ?? null;
        unset($validated['account_group_id']);
        $validated['consider_for_accounting'] = (bool) ($validated['consider_for_accounting'] ?? false);

        $agent->update($validated);
        $linker->syncName($agent);

        if ($agent->consider_for_accounting) {
            $linker->enable($agent, $accountGroupId ? (int) $accountGroupId : null);
        } else {
            $linker->disable($agent);
        }

        return redirect()->back()->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent)
    {
        $agent->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
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
            'transport_id' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'consider_for_accounting' => 'sometimes|boolean',
            'account_group_id' => 'nullable|exists:account_groups,id',
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
            'transport_id.size' => 'Transport id must be exactly 15 characters.',
            'transport_id.regex' => 'Transport id format is invalid.',
        ];
    }
}
