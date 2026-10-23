<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\City;
use App\Models\CompanyStationAgent;
use App\Models\State;
use App\Models\Station;
use App\Services\BulkUploadService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view stations', only: ['index', 'show', 'quickShow']),
            new Middleware('permission:create stations', only: ['create', 'store', 'quickStore', 'quickFormData', 'bulkUpload', 'bulkUploadTemplate']),
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
            ->orderBy('name', 'asc')
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

    public function show(Station $station)
    {
        $station->load(['state', 'city', 'country']);

        $companyId = app(TenantContext::class)->activeCompanyId();

        $defaultAgent = $companyId
            ? CompanyStationAgent::query()
                ->with('agent:id,name')
                ->where('company_id', $companyId)
                ->where('station_id', $station->id)
                ->first()?->agent
            : null;

        return Inertia::render('Stations/Show', [
            'station' => $station,
            'default_agent' => $defaultAgent,
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
        $station->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
        $station->delete();

        return redirect()->route('stations.index')->with('success', 'Station deleted successfully.');
    }

    public function bulkUploadTemplate(BulkUploadService $bulkUploadService): StreamedResponse
    {
        return $bulkUploadService->downloadTemplate(
            headers: ['Station Name', 'State', 'City', 'Default Agent'],
            fileName: 'stations_bulk_upload_template.csv'
        );
    }

    public function bulkUpload(Request $request, BulkUploadService $bulkUploadService): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'extensions:csv,xlsx,xls', 'max:5120'],
        ], [
            'file.mimes' => 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).',
            'file.extensions' => 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).',
        ]);

        $companyId = app(TenantContext::class)->activeCompanyId();

        // Preload states for fast lookup
        $states = State::all();
        $statesByName = [];
        $statesByCode = [];
        $statesById = [];
        foreach ($states as $s) {
            $statesByName[mb_strtolower(trim($s->name))] = $s;
            if (! empty($s->code)) {
                $statesByCode[mb_strtolower(trim($s->code))] = $s;
            }
            $statesById[$s->id] = $s;
        }

        // Preload company agents
        $agentsByName = [];
        $agentsById = [];
        if ($companyId) {
            $companyAgents = Agent::query()
                ->where('company_id', $companyId)
                ->get(['id', 'name']);

            foreach ($companyAgents as $ag) {
                $agentsByName[mb_strtolower(trim($ag->name))] = $ag;
                $agentsById[$ag->id] = $ag;
            }
        }

        // Cache resolved cities
        $citiesCache = [];

        $rowValidator = function (array &$rowData, int $rowNumber) use (
            &$statesByName,
            &$statesByCode,
            &$statesById,
            &$agentsByName,
            &$agentsById,
            &$citiesCache,
            $companyId
        ): array {
            $errors = [];

            // 1. Resolve State
            $stateRaw = trim((string) ($rowData['state'] ?? ''));
            $stateLower = mb_strtolower($stateRaw);
            $resolvedState = $statesByName[$stateLower]
                ?? $statesByCode[$stateLower]
                ?? ($statesById[(int) $stateRaw] ?? null);

            if (! $resolvedState) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'State',
                    'value' => $stateRaw,
                    'message' => sprintf('State "%s" was not found in state records.', $stateRaw),
                ];

                return $errors;
            }

            $rowData['state_id'] = $resolvedState->id;
            $rowData['country_id'] = $resolvedState->country_id;

            // 2. Validate City (emptiness already enforced by columnSpecs required rules)
            $cityRaw = trim((string) ($rowData['city'] ?? ''));
            $cityLower = mb_strtolower($cityRaw);

            if (! isset($citiesCache[$resolvedState->id])) {
                $citiesCache[$resolvedState->id] = [];
            }

            if (isset($citiesCache[$resolvedState->id][$cityLower])) {
                $existingCity = $citiesCache[$resolvedState->id][$cityLower];
            } else {
                $existingCity = City::query()
                    ->where('state_id', $resolvedState->id)
                    ->where(function ($q) use ($cityLower, $cityRaw) {
                        $q->whereRaw('LOWER(name) = ?', [$cityLower]);
                        if (is_numeric($cityRaw)) {
                            $q->orWhere('id', (int) $cityRaw);
                        }
                    })
                    ->first();

                if ($existingCity) {
                    $citiesCache[$resolvedState->id][$cityLower] = $existingCity;
                }
            }

            if (! $existingCity) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'City',
                    'value' => $cityRaw,
                    'message' => sprintf('City "%s" was not found in city records for state "%s".', $cityRaw, $resolvedState->name),
                ];

                return $errors;
            }

            $rowData['city_id'] = $existingCity->id;

            // 3. Resolve Default Agent
            $agentRaw = trim((string) ($rowData['default_agent'] ?? ''));
            if ($agentRaw !== '') {
                if (! $companyId) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Default Agent',
                        'value' => $agentRaw,
                        'message' => 'Cannot assign default agent without an active company context.',
                    ];
                } else {
                    $agentLower = mb_strtolower($agentRaw);
                    $resolvedAgent = $agentsByName[$agentLower]
                        ?? ($agentsById[(int) $agentRaw] ?? null);

                    if (! $resolvedAgent) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'field' => 'Default Agent',
                            'value' => $agentRaw,
                            'message' => sprintf('Agent "%s" not found for the active company.', $agentRaw),
                        ];
                    } else {
                        $rowData['default_agent_id'] = $resolvedAgent->id;
                    }
                }
            } else {
                $rowData['default_agent_id'] = null;
            }

            return $errors;
        };

        $saveHandler = function (array $data, int $rowNumber) use ($companyId) {
            $station = Station::create([
                'name' => $data['name'],
                'state_id' => $data['state_id'],
                'city_id' => $data['city_id'],
                'country_id' => $data['country_id'] ?? null,
            ]);

            $defaultAgentId = $data['default_agent_id'] ?? null;
            if ($companyId && $defaultAgentId) {
                $this->syncDefaultAgent($station, (int) $defaultAgentId);
            }

            return $station;
        };

        $result = $bulkUploadService->process(
            file: $request->file('file'),
            modelClass: Station::class,
            columnSpecs: [
                'name' => [
                    'label' => 'Station Name',
                    'required' => true,
                    'aliases' => ['name', 'station_name', 'station'],
                    'rules' => ['required', 'string', 'max:255'],
                    'unique' => true,
                ],
                'state' => [
                    'label' => 'State',
                    'required' => true,
                    'aliases' => ['state', 'state_name', 'state_code'],
                    'rules' => ['required', 'string', 'max:255'],
                ],
                'city' => [
                    'label' => 'City',
                    'required' => true,
                    'aliases' => ['city', 'city_name'],
                    'rules' => ['required', 'string', 'max:255'],
                ],
                'default_agent' => [
                    'label' => 'Default Agent',
                    'required' => false,
                    'aliases' => ['default_agent', 'agent', 'agency', 'default_agency', 'agent_name'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
            ],
            beforeSave: null,
            rowValidator: $rowValidator,
            saveHandler: $saveHandler,
        );

        return response()->json($result);
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
