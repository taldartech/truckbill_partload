<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\City;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\TruckType;
use App\Services\BulkUploadService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TruckController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view trucks', only: ['index', 'show']),
            new Middleware('permission:create trucks', only: ['create', 'store', 'quickStore', 'quickFormData', 'bulkUpload', 'bulkUploadTemplate']),
            new Middleware('permission:edit trucks', only: ['edit', 'update']),
            new Middleware('permission:delete trucks', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $trucks = Truck::query()
            ->with(['company', 'truckType', 'currentState', 'currentCity', 'currentStation', 'agent'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('truck_number', 'like', '%'.$search.'%')
                        ->orWhere('status', 'like', '%'.$search.'%')
                        ->orWhereHas('agent', function ($ownerQuery) use ($search) {
                            $ownerQuery->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Trucks/Index', [
            'trucks' => $trucks,
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Truck $truck)
    {
        $truck->load([
            'company',
            'truckType',
            'currentState',
            'currentCity',
            'currentStation',
            'agent.state',
            'agent.city',
        ]);

        return Inertia::render('Trucks/Show', [
            'truck' => $truck,
        ]);
    }

    public function create()
    {
        return Inertia::render('Trucks/Create', [
            'truckTypes' => TruckType::all(),
            'agents' => $this->agentOptions(),
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate($this->truckRules()));

        if (! empty($validated['truck_number'])) {
            $validated['truck_number'] = strtoupper($validated['truck_number']);
        }

        Truck::create($validated);

        return redirect()->route('trucks.index')->with('success', 'Truck created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        return response()->json([
            'truckTypes' => TruckType::select('id', 'name')->orderBy('name')->get(),
            'agents' => $this->agentOptions(),
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'truck_number' => 'required|string|max:20',
            'truck_type_id' => 'nullable|exists:truck_types,id',
            'agent_id' => 'nullable|exists:agents,id',
        ]));

        $validated['truck_number'] = strtoupper($validated['truck_number']);

        $truck = Truck::create($validated);

        return response()->json([
            'truck' => [
                'id' => $truck->id,
                'name' => $truck->truck_number,
            ],
        ], 201);
    }

    public function edit(Truck $truck)
    {
        return Inertia::render('Trucks/Edit', [
            'truck' => $truck,
            'truckTypes' => TruckType::all(),
            'agents' => $this->agentOptions(),
            'states' => State::all(),
            'cities' => City::all(),
            'stations' => Station::all(),
        ]);
    }

    public function update(Request $request, Truck $truck)
    {
        $validated = $this->stripTenantFields($request->validate($this->truckRules()));

        if (! empty($validated['truck_number'])) {
            $validated['truck_number'] = strtoupper($validated['truck_number']);
        }

        $truck->update($validated);

        return redirect()->route('trucks.index')->with('success', 'Truck updated successfully.');
    }

    public function destroy(Truck $truck)
    {
        $truck->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
        $truck->delete();

        return redirect()->route('trucks.index')->with('success', 'Truck deleted successfully.');
    }

    public function completeTour(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'current_state_id' => 'required|exists:states,id',
            'current_city_id' => 'required|exists:cities,id',
            'current_station_id' => 'nullable|exists:stations,id',
        ]);

        $truck->update([
            'status' => $validated['status'],
            'current_state_id' => $validated['current_state_id'],
            'current_city_id' => $validated['current_city_id'],
            'current_station_id' => $validated['current_station_id'],
        ]);

        return redirect()->back()->with('success', 'Truck status and location updated successfully.');
    }

    /**
     * @return array<string, string>
     */
    private function truckRules(): array
    {
        return [
            'truck_number' => 'nullable|string|max:20',
            'truck_type_id' => 'nullable|exists:truck_types,id',
            'status' => 'nullable|string|max:50',
            'current_state_id' => 'nullable|exists:states,id',
            'current_city_id' => 'nullable|exists:cities,id',
            'current_station_id' => 'nullable|exists:stations,id',
            'capacity' => 'nullable|string|max:50',
            'agent_id' => 'nullable|exists:agents,id',
            'rc_no' => 'nullable|string|max:50',
            'rc_expiry_date' => 'nullable|date',
            'insurance_no' => 'nullable|string|max:50',
            'insurance_expiry_date' => 'nullable|date',
            'fitness_no' => 'nullable|string|max:50',
            'fitness_expiry_date' => 'nullable|date',
            'pollution_no' => 'nullable|string|max:50',
            'pollution_expiry_date' => 'nullable|date',
            'permit_no' => 'nullable|string|max:50',
            'permit_expiry_date' => 'nullable|date',
        ];
    }

    private function agentOptions()
    {
        return Agent::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function bulkUploadTemplate(BulkUploadService $bulkUploadService): StreamedResponse
    {
        return $bulkUploadService->downloadTemplate(
            headers: [
                'Truck Number',
                'Truck Type',
                'Capacity',
                'Status',
                'State',
                'City',
                'Station',
                'Truck Owner',
                'Driver Name',
                'RC Number',
                'RC Expiry Date',
                'Insurance No',
                'Insurance Expiry Date',
                'Fitness No',
                'Fitness Expiry Date',
                'Pollution No',
                'Pollution Expiry Date',
                'Permit No',
                'Permit Expiry Date',
            ],
            fileName: 'trucks_bulk_upload_template.csv'
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

        // 1. Preload states
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

        // 2. Preload truck types
        $truckTypes = TruckType::all();
        $truckTypesByName = [];
        $truckTypesById = [];
        foreach ($truckTypes as $tt) {
            $truckTypesByName[mb_strtolower(trim($tt->name))] = $tt;
            $truckTypesById[$tt->id] = $tt;
        }

        // 3. Preload agents (truck owners map to agents)
        $agents = $companyId ? Agent::where('company_id', $companyId)->get() : collect();
        $agentsByName = [];
        $agentsById = [];
        foreach ($agents as $agent) {
            $agentsByName[mb_strtolower(trim($agent->name))] = $agent;
            $agentsById[$agent->id] = $agent;
        }

        // 4. Preload stations
        $stations = Station::all();
        $stationsByName = [];
        $stationsById = [];
        foreach ($stations as $st) {
            $stationsByName[mb_strtolower(trim($st->name))] = $st;
            $stationsById[$st->id] = $st;
        }

        // Cache resolved cities
        $citiesCache = [];

        $rowValidator = function (array &$rowData, int $rowNumber) use (
            &$statesByName,
            &$statesByCode,
            &$statesById,
            &$truckTypesByName,
            &$truckTypesById,
            &$agentsByName,
            &$agentsById,
            &$stationsByName,
            &$stationsById,
            &$citiesCache
        ): array {
            $errors = [];

            // 1. Normalize truck number (emptiness already enforced by columnSpecs required rules)
            $truckNumberRaw = trim((string) ($rowData['truck_number'] ?? ''));
            $rowData['truck_number'] = strtoupper($truckNumberRaw);

            // 2. Truck Type
            $truckTypeRaw = trim((string) ($rowData['truck_type'] ?? ''));
            if ($truckTypeRaw !== '') {
                $ttLower = mb_strtolower($truckTypeRaw);
                $resolvedTt = $truckTypesByName[$ttLower] ?? ($truckTypesById[(int) $truckTypeRaw] ?? null);
                if (! $resolvedTt) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Truck Type',
                        'value' => $truckTypeRaw,
                        'message' => sprintf('Truck Type "%s" was not found in truck types.', $truckTypeRaw),
                    ];
                } else {
                    $rowData['truck_type_id'] = $resolvedTt->id;
                }
            }
            unset($rowData['truck_type']);

            // 3. Status
            $statusRaw = trim((string) ($rowData['status'] ?? ''));
            if ($statusRaw !== '') {
                $validStatuses = ['Available', 'Booked', 'In Transit', 'Maintenance'];
                $matchedStatus = null;
                foreach ($validStatuses as $vs) {
                    if (strcasecmp($vs, $statusRaw) === 0) {
                        $matchedStatus = $vs;
                        break;
                    }
                }
                if (! $matchedStatus) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Status',
                        'value' => $statusRaw,
                        'message' => 'Status must be one of: Available, Booked, In Transit, Maintenance.',
                    ];
                } else {
                    $rowData['status'] = $matchedStatus;
                }
            } else {
                $rowData['status'] = 'Available';
            }

            // 4. Truck Owner (agent)
            $ownerRaw = trim((string) ($rowData['truck_owner'] ?? ''));
            if ($ownerRaw !== '') {
                $ownerLower = mb_strtolower($ownerRaw);
                $resolvedAgent = $agentsByName[$ownerLower] ?? ($agentsById[(int) $ownerRaw] ?? null);
                if (! $resolvedAgent) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Truck Owner',
                        'value' => $ownerRaw,
                        'message' => sprintf('Truck Owner "%s" was not found in agent records.', $ownerRaw),
                    ];
                } else {
                    $rowData['agent_id'] = $resolvedAgent->id;
                }
            }
            unset($rowData['truck_owner']);

            // 5. State & City
            $stateRaw = trim((string) ($rowData['state'] ?? ''));
            $cityRaw = trim((string) ($rowData['city'] ?? ''));

            $resolvedState = null;
            if ($stateRaw !== '') {
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
                } else {
                    $rowData['current_state_id'] = $resolvedState->id;
                }
            }
            unset($rowData['state']);

            if ($cityRaw !== '') {
                if (! $resolvedState) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'City',
                        'value' => $cityRaw,
                        'message' => 'State is required to resolve City.',
                    ];
                } else {
                    $cityLower = mb_strtolower($cityRaw);
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
                    } else {
                        $rowData['current_city_id'] = $existingCity->id;
                    }
                }
            }
            unset($rowData['city']);

            // 6. Station
            $stationRaw = trim((string) ($rowData['station'] ?? ''));
            if ($stationRaw !== '') {
                $stationLower = mb_strtolower($stationRaw);
                $resolvedStation = $stationsByName[$stationLower] ?? ($stationsById[(int) $stationRaw] ?? null);
                if (! $resolvedStation) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Station',
                        'value' => $stationRaw,
                        'message' => sprintf('Station "%s" was not found in station records.', $stationRaw),
                    ];
                } else {
                    $rowData['current_station_id'] = $resolvedStation->id;
                }
            }
            unset($rowData['station']);

            // 7. Driver
            unset($rowData['driver']);

            // 8. Clean up empty string values for dates and compliance fields
            $dateFields = ['rc_expiry_date', 'insurance_expiry_date', 'fitness_expiry_date', 'pollution_expiry_date', 'permit_expiry_date'];
            foreach ($dateFields as $df) {
                if (array_key_exists($df, $rowData) && trim((string) $rowData[$df]) === '') {
                    $rowData[$df] = null;
                }
            }

            $stringFields = ['rc_no', 'insurance_no', 'fitness_no', 'pollution_no', 'permit_no', 'capacity'];
            foreach ($stringFields as $sf) {
                if (array_key_exists($sf, $rowData) && trim((string) $rowData[$sf]) === '') {
                    $rowData[$sf] = null;
                }
            }

            return $errors;
        };

        $result = $bulkUploadService->process(
            file: $request->file('file'),
            modelClass: Truck::class,
            columnSpecs: [
                'truck_number' => [
                    'label' => 'Truck Number',
                    'required' => true,
                    'aliases' => ['truck_number', 'truck_no', 'truck', 'vehicle_number', 'vehicle_no'],
                    'rules' => ['required', 'string', 'max:20'],
                    'unique' => true,
                ],
                'truck_type' => [
                    'label' => 'Truck Type',
                    'required' => false,
                    'aliases' => ['truck_type', 'truck_type_name', 'type'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'capacity' => [
                    'label' => 'Capacity',
                    'required' => false,
                    'aliases' => ['capacity', 'capacity_tons', 'capacity_in_tons', 'tonnage'],
                    'rules' => ['nullable', 'string', 'max:50'],
                ],
                'status' => [
                    'label' => 'Status',
                    'required' => false,
                    'aliases' => ['status', 'truck_status'],
                    'rules' => ['nullable', 'string', 'max:50'],
                ],
                'state' => [
                    'label' => 'State',
                    'required' => false,
                    'aliases' => ['state', 'current_state', 'state_name'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'city' => [
                    'label' => 'City',
                    'required' => false,
                    'aliases' => ['city', 'current_city', 'city_name'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'station' => [
                    'label' => 'Station',
                    'required' => false,
                    'aliases' => ['station', 'current_station', 'station_name'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'truck_owner' => [
                    'label' => 'Truck Owner',
                    'required' => false,
                    'aliases' => ['truck_owner', 'owner', 'owner_name', 'transporter'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'driver' => [
                    'label' => 'Driver Name',
                    'required' => false,
                    'aliases' => ['driver', 'driver_name'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'rc_no' => [
                    'label' => 'RC Number',
                    'required' => false,
                    'aliases' => ['rc_no', 'rc_number', 'rc'],
                    'rules' => ['nullable', 'string', 'max:50'],
                ],
                'rc_expiry_date' => [
                    'label' => 'RC Expiry Date',
                    'required' => false,
                    'aliases' => ['rc_expiry_date', 'rc_expiry'],
                    'rules' => ['nullable', 'date'],
                ],
                'insurance_no' => [
                    'label' => 'Insurance No',
                    'required' => false,
                    'aliases' => ['insurance_no', 'insurance_number', 'policy_no'],
                    'rules' => ['nullable', 'string', 'max:50'],
                ],
                'insurance_expiry_date' => [
                    'label' => 'Insurance Expiry Date',
                    'required' => false,
                    'aliases' => ['insurance_expiry_date', 'insurance_expiry'],
                    'rules' => ['nullable', 'date'],
                ],
                'fitness_no' => [
                    'label' => 'Fitness No',
                    'required' => false,
                    'aliases' => ['fitness_no', 'fitness_number'],
                    'rules' => ['nullable', 'string', 'max:50'],
                ],
                'fitness_expiry_date' => [
                    'label' => 'Fitness Expiry Date',
                    'required' => false,
                    'aliases' => ['fitness_expiry_date', 'fitness_expiry'],
                    'rules' => ['nullable', 'date'],
                ],
                'pollution_no' => [
                    'label' => 'Pollution No',
                    'required' => false,
                    'aliases' => ['pollution_no', 'pollution_number', 'puc_no'],
                    'rules' => ['nullable', 'string', 'max:50'],
                ],
                'pollution_expiry_date' => [
                    'label' => 'Pollution Expiry Date',
                    'required' => false,
                    'aliases' => ['pollution_expiry_date', 'pollution_expiry', 'puc_expiry_date'],
                    'rules' => ['nullable', 'date'],
                ],
                'permit_no' => [
                    'label' => 'Permit No',
                    'required' => false,
                    'aliases' => ['permit_no', 'permit_number'],
                    'rules' => ['nullable', 'string', 'max:50'],
                ],
                'permit_expiry_date' => [
                    'label' => 'Permit Expiry Date',
                    'required' => false,
                    'aliases' => ['permit_expiry_date', 'permit_expiry'],
                    'rules' => ['nullable', 'date'],
                ],
            ],
            rowValidator: $rowValidator
        );

        return response()->json($result);
    }
}
