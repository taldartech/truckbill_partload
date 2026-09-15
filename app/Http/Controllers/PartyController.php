<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Group;
use App\Models\Party;
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

class PartyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view parties', only: ['index', 'show', 'quickShow']),
            new Middleware('permission:create parties', only: ['create', 'store', 'quickStore', 'quickFormData', 'bulkUpload', 'bulkUploadTemplate']),
            new Middleware('permission:edit parties', only: ['edit', 'update']),
            new Middleware('permission:delete parties', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $parties = Party::query()
            ->with('company')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('gst_number', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Parties/Index', [
            'parties' => $parties,
            'groups' => Group::query()->orderBy('name')->get(['id', 'name']),
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'state_id']),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Party $party)
    {
        $party->load(['group', 'state', 'city', 'company']);

        return Inertia::render('Parties/Show', [
            'party' => $party,
        ]);
    }

    public function create()
    {
        return redirect()->route('parties.index');
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->partyRules(),
            $this->partyMessages(),
        ));

        Party::create($validated);

        return redirect()->back()->with('success', 'Party created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        return response()->json([
            'groups' => Group::select('id', 'name')->orderBy('name')->get(),
            'states' => State::select('id', 'name')->orderBy('name')->get(),
            'default_company_id' => $this->companyId(),
        ]);
    }

    public function quickShow(Party $party): JsonResponse
    {
        $party->load(['state', 'city', 'group', 'company']);

        return response()->json([
            'rows' => [
                ['label' => 'Name', 'value' => $party->name],
                ['label' => 'Address', 'value' => $party->address],
                ['label' => 'State', 'value' => $party->state?->name],
                ['label' => 'City', 'value' => $party->city?->name],
                ['label' => 'Mobile', 'value' => $party->mobile],
                ['label' => 'Email', 'value' => $party->email],
                ['label' => 'GST Number', 'value' => $party->gst_number],
                ['label' => 'Group', 'value' => $party->group?->name],
            ],
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->quickPartyRules(),
            $this->partyMessages(),
        ));

        $party = Party::create($validated);
        $party->load(['state:id,name', 'city:id,name']);

        return response()->json([
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'gst_number' => $party->gst_number,
                'mobile' => $party->mobile,
                'state_id' => $party->state_id,
                'city_id' => $party->city_id,
                'state' => $party->state,
                'city' => $party->city,
                'bilty_charge' => $party->bilty_charge,
            ],
        ], 201);
    }

    public function edit(Party $party)
    {
        return redirect()->route('parties.index');
    }

    public function update(Request $request, Party $party)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->partyRules(),
            $this->partyMessages(),
        ));

        $party->update($validated);

        return redirect()->back()->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party)
    {
        $party->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
        $party->delete();

        return redirect()->route('parties.index')->with('success', 'Party deleted successfully.');
    }

    public function bulkUploadTemplate(BulkUploadService $bulkUploadService): StreamedResponse
    {
        return $bulkUploadService->downloadTemplate(
            headers: [
                'Party Name',
                'Address',
                'State',
                'City',
                'Mobile',
                'Email',
                'GST Number',
                'Group',
                'Bilty Charge',
            ],
            sampleRows: [],
            fileName: 'parties_bulk_upload_template.csv'
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

        // Preload company groups
        $groupsByName = [];
        $groupsById = [];
        if ($companyId) {
            $companyGroups = Group::query()
                ->where('company_id', $companyId)
                ->get(['id', 'name']);

            foreach ($companyGroups as $grp) {
                $groupsByName[mb_strtolower(trim($grp->name))] = $grp;
                $groupsById[$grp->id] = $grp;
            }
        }

        // Cache resolved cities: [state_id => [lowercase_city_name => City]]
        $citiesCache = [];

        $rowValidator = function (array &$rowData, int $rowNumber) use (
            &$statesByName,
            &$statesByCode,
            &$statesById,
            &$groupsByName,
            &$groupsById,
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

            // 3. Resolve Group
            $groupRaw = trim((string) ($rowData['group'] ?? ''));
            if ($groupRaw !== '') {
                $groupLower = mb_strtolower($groupRaw);
                $resolvedGroup = $groupsByName[$groupLower]
                    ?? ($groupsById[(int) $groupRaw] ?? null);

                if (! $resolvedGroup) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Group',
                        'value' => $groupRaw,
                        'message' => sprintf('Group "%s" was not found in group records.', $groupRaw),
                    ];
                } else {
                    $rowData['group_id'] = $resolvedGroup->id;
                }
            } else {
                $rowData['group_id'] = null;
            }

            if (isset($rowData['email']) && trim((string) $rowData['email']) === '') {
                $rowData['email'] = null;
            }
            if (isset($rowData['gst_number']) && trim((string) $rowData['gst_number']) === '') {
                $rowData['gst_number'] = null;
            }
            if (isset($rowData['bilty_charge'])) {
                $biltyChargeRaw = trim((string) $rowData['bilty_charge']);
                $rowData['bilty_charge'] = $biltyChargeRaw === '' ? null : (float) $biltyChargeRaw;
            } else {
                $rowData['bilty_charge'] = null;
            }

            return $errors;
        };

        $saveHandler = function (array $data, int $rowNumber) use ($companyId) {
            return Party::create([
                'company_id' => $companyId,
                'group_id' => $data['group_id'] ?? null,
                'name' => $data['name'],
                'address' => $data['address'],
                'state_id' => $data['state_id'],
                'city_id' => $data['city_id'],
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? null,
                'gst_number' => $data['gst_number'] ?? null,
                'bilty_charge' => $data['bilty_charge'] ?? null,
            ]);
        };

        $result = $bulkUploadService->process(
            file: $request->file('file'),
            modelClass: Party::class,
            columnSpecs: [
                'name' => [
                    'label' => 'Party Name',
                    'required' => true,
                    'aliases' => ['name', 'party_name', 'party'],
                    'rules' => ['required', 'string', 'max:255'],
                    'unique' => true,
                ],
                'address' => [
                    'label' => 'Address',
                    'required' => true,
                    'aliases' => ['address', 'party_address'],
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
                'gst_number' => [
                    'label' => 'GST Number',
                    'required' => false,
                    'aliases' => ['gst_number', 'gst_no', 'gst', 'gstin'],
                    'rules' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
                ],
                'group' => [
                    'label' => 'Group',
                    'required' => false,
                    'aliases' => ['group', 'group_name', 'party_group'],
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'bilty_charge' => [
                    'label' => 'Bilty Charge',
                    'required' => false,
                    'aliases' => ['bilty_charge', 'bilty_charges', 'fixed_bilty_charge'],
                    'rules' => ['nullable', 'numeric', 'min:0'],
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
    private function partyRules(): array
    {
        return [
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'mobile' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'bilty_charge' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function quickPartyRules(): array
    {
        return [
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'mobile' => 'required|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function partyMessages(): array
    {
        return [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
        ];
    }
}
