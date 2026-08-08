<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\City;
use App\Models\CompanyStationAgent;
use App\Models\State;
use App\Models\Station;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $companyId = app(TenantContext::class)->activeCompanyId();

        $stations = Station::query()
            ->select('stations.*')
            ->with('city', 'state')
            ->when($companyId, function ($query) use ($companyId) {
                $query->addSelect([
                    'default_agent_id' => CompanyStationAgent::query()
                        ->select('agent_id')
                        ->whereColumn('company_station_agents.station_id', 'stations.id')
                        ->where('company_station_agents.company_id', $companyId)
                        ->limit(1),
                ]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('city', fn ($cityQuery) => $cityQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('state', fn ($stateQuery) => $stateQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Stations/Index', [
            'stations' => $stations,
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'state_id']),
            'agents' => $companyId
                ? Agent::query()->orderBy('name')->get(['id', 'name', 'city_id', 'state_id'])
                : [],
            'can_set_default_agent' => (bool) $companyId,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return redirect()->route('stations.index');
    }

    public function store(Request $request)
    {
        $companyId = app(TenantContext::class)->activeCompanyId();

        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'default_agent_id' => [
                'nullable',
                ...($companyId
                    ? [Rule::exists('agents', 'id')->where('company_id', $companyId)]
                    : []),
            ],
        ]));

        $defaultAgentId = $validated['default_agent_id'] ?? null;
        unset($validated['default_agent_id']);

        $station = Station::create($validated);
        $this->syncDefaultAgent($station, $defaultAgentId !== null && $defaultAgentId !== '' ? (int) $defaultAgentId : null);

        return redirect()->back()->with('success', 'Station created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        $companyId = app(TenantContext::class)->activeCompanyId();

        return response()->json([
            'states' => State::select('id', 'name')->orderBy('name')->get(),
            'agents' => $companyId
                ? Agent::query()->orderBy('name')->get(['id', 'name', 'city_id', 'state_id'])
                : [],
            'can_set_default_agent' => (bool) $companyId,
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
        $companyId = app(TenantContext::class)->activeCompanyId();

        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'default_agent_id' => [
                'nullable',
                ...($companyId
                    ? [Rule::exists('agents', 'id')->where('company_id', $companyId)]
                    : []),
            ],
        ]));

        $defaultAgentId = $validated['default_agent_id'] ?? null;
        unset($validated['default_agent_id']);

        $station = Station::create($validated);
        $this->syncDefaultAgent(
            $station,
            $defaultAgentId !== null && $defaultAgentId !== '' ? (int) $defaultAgentId : null,
        );

        $countryId = State::query()
            ->whereKey($station->state_id)
            ->value('country_id');

        $defaultAgentName = null;
        if ($companyId && $defaultAgentId) {
            $defaultAgentName = Agent::query()
                ->whereKey((int) $defaultAgentId)
                ->where('company_id', $companyId)
                ->value('name');
        }

        return response()->json([
            'station' => [
                'id' => $station->id,
                'name' => $station->name,
                'country_id' => $countryId,
                'state_id' => $station->state_id,
                'city_id' => $station->city_id,
                'default_agent_name' => $defaultAgentName,
            ],
        ], 201);
    }

    public function edit(Station $station)
    {
        return redirect()->route('stations.index');
    }

    public function update(Request $request, Station $station)
    {
        $companyId = app(TenantContext::class)->activeCompanyId();

        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'default_agent_id' => [
                'nullable',
                ...($companyId
                    ? [Rule::exists('agents', 'id')->where('company_id', $companyId)]
                    : []),
            ],
        ]));

        $defaultAgentId = $validated['default_agent_id'] ?? null;
        unset($validated['default_agent_id']);

        $station->update($validated);
        $this->syncDefaultAgent($station, $defaultAgentId !== null && $defaultAgentId !== '' ? (int) $defaultAgentId : null);

        return redirect()->back()->with('success', 'Station updated successfully.');
    }

    public function destroy(Station $station)
    {
        $station->delete();

        return redirect()->back()->with('success', 'Station deleted successfully.');
    }

    private function syncDefaultAgent(Station $station, ?int $agentId): void
    {
        $companyId = app(TenantContext::class)->activeCompanyId();

        if (! $companyId) {
            return;
        }

        if (! $agentId) {
            CompanyStationAgent::query()
                ->where('company_id', $companyId)
                ->where('station_id', $station->id)
                ->delete();

            return;
        }

        $agentBelongsToCompany = Agent::query()
            ->whereKey($agentId)
            ->where('company_id', $companyId)
            ->exists();

        if (! $agentBelongsToCompany) {
            throw ValidationException::withMessages([
                'default_agent_id' => 'Selected agent does not belong to the active company.',
            ]);
        }

        CompanyStationAgent::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'station_id' => $station->id,
            ],
            [
                'agent_id' => $agentId,
            ],
        );
    }
}
