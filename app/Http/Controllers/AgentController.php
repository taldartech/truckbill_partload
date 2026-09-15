<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\City;
use App\Models\State;
use App\Services\BulkUploadService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view agents', only: ['index', 'show']),
            new Middleware('permission:create agents', only: ['create', 'store', 'quickStore', 'quickFormData', 'bulkUpload', 'bulkUploadTemplate']),
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

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->agentRules(),
            $this->agentMessages(),
        ));

        Agent::create($validated);

        return redirect()->back()->with('success', 'Agent created successfully.');
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
        return redirect()->route('agents.index');
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->agentRules(),
            $this->agentMessages(),
        ));

        $agent->update($validated);

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

    public function bulkUploadTemplate(BulkUploadService $bulkUploadService): StreamedResponse
    {
        return $bulkUploadService->downloadTemplate(
            headers: [
                'Agent Name',
                'Address',
                'State',
                'City',
                'Mobile',
                'Email',
                'GST Number',
                'Transporter ID',
            ],
            sampleRows: [],
            fileName: 'agents_bulk_upload_template.csv'
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

        // Cache resolved cities
        $citiesCache = [];

        $rowValidator = function (array &$rowData, int $rowNumber) use (
            &$statesByName,
            &$statesByCode,
            &$statesById,
            &$citiesCache
        ): array {
            $errors = [];

            // 1. Resolve State
            $stateRaw = trim((string) ($rowData['state'] ?? ''));
            if ($stateRaw === '') {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'State',
                    'value' => '',
                    'message' => 'State is required.',
                ];

                return $errors;
            }

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

            // 2. Validate City
            $cityRaw = trim((string) ($rowData['city'] ?? ''));
            if ($cityRaw === '') {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'City',
                    'value' => '',
                    'message' => 'City is required.',
                ];

                return $errors;
            }

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

            if (isset($rowData['email']) && trim((string) $rowData['email']) === '') {
                $rowData['email'] = null;
            }
            if (isset($rowData['gst_no']) && trim((string) $rowData['gst_no']) === '') {
                $rowData['gst_no'] = null;
            }
            if (isset($rowData['transport_id']) && trim((string) $rowData['transport_id']) === '') {
                $rowData['transport_id'] = null;
            }

            return $errors;
        };

        $saveHandler = function (array $data) use ($companyId) {
            return Agent::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'address' => $data['address'],
                'state_id' => $data['state_id'],
                'city_id' => $data['city_id'],
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? null,
                'gst_no' => $data['gst_no'] ?? null,
                'transport_id' => $data['transport_id'] ?? null,
            ]);
        };

        $result = $bulkUploadService->process(
            file: $request->file('file'),
            modelClass: Agent::class,
            columnSpecs: [
                'name' => [
                    'label' => 'Agent Name',
                    'required' => true,
                    'aliases' => ['name', 'agent_name', 'agent'],
                    'rules' => ['required', 'string', 'max:255'],
                    'unique' => true,
                ],
                'address' => [
                    'label' => 'Address',
                    'required' => true,
                    'aliases' => ['address', 'agent_address'],
                    'rules' => ['required', 'string', 'max:500'],
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
                'mobile' => [
                    'label' => 'Mobile',
                    'required' => true,
                    'aliases' => ['mobile', 'mobile_no', 'phone', 'contact_no'],
                    'rules' => ['required', 'string', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
                ],
                'email' => [
                    'label' => 'Email',
                    'required' => false,
                    'aliases' => ['email', 'email_address'],
                    'rules' => ['nullable', 'string', 'email', 'max:255'],
                ],
                'gst_no' => [
                    'label' => 'GST Number',
                    'required' => false,
                    'aliases' => ['gst_no', 'gst', 'gstin'],
                    'rules' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
                ],
                'transport_id' => [
                    'label' => 'Transporter ID',
                    'required' => false,
                    'aliases' => ['transport_id', 'transporter_id'],
                    'rules' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
                ],
            ],
            beforeSave: null,
            rowValidator: $rowValidator,
            saveHandler: $saveHandler,
        );

        return response()->json($result);
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
