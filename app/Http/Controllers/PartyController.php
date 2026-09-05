<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Group;
use App\Models\Party;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PartyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view parties', only: ['index', 'show', 'quickShow']),
            new Middleware('permission:create parties', only: ['create', 'store', 'quickStore', 'quickFormData']),
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
