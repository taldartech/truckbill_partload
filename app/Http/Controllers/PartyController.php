<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Company;
use App\Models\Group;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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

    public function index()
    {
        $parties = Party::with('company')->latest()->paginate(10);
        return Inertia::render('Parties/Index', [
            'parties' => $parties
        ]);
    }

    public function create()
    {
        return Inertia::render('Parties/Create', [
            'companies' => Company::all(),
            'groups' => Group::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'nullable|string',
            'type' => 'nullable|string',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string',
            'mobile_sms' => 'nullable|string',
            'email' => 'nullable|email',
            'gst_number' => 'nullable|string',
            'bilty_charge' => 'nullable|numeric'
        ]);

        Party::create($validated);
        return redirect()->route('parties.index')->with('success', 'Party created successfully.');
    }

    public function quickFormData(): JsonResponse
    {
        $data = [
            'groups' => Group::select('id', 'name')->orderBy('name')->get(),
            'states' => State::select('id', 'name')->orderBy('name')->get(),
            'default_company_id' => auth()->user()?->company_id,
        ];

        if (auth()->user()?->hasRole(['master_admin', 'super_admin'])) {
            $data['companies'] = Company::select('id', 'name')->orderBy('name')->get();
        }

        return response()->json($data);
    }

    public function quickShow(Party $party): JsonResponse
    {
        $party->load(['state', 'city', 'group', 'company']);

        return response()->json([
            'rows' => [
                ['label' => 'Name', 'value' => $party->name],
                ['label' => 'Type', 'value' => $party->type],
                ['label' => 'Address', 'value' => $party->address],
                ['label' => 'State', 'value' => $party->state?->name],
                ['label' => 'City', 'value' => $party->city?->name],
                ['label' => 'Mobile', 'value' => $party->mobile],
                ['label' => 'Email', 'value' => $party->email],
                ['label' => 'GST Number', 'value' => $party->gst_number],
                ['label' => 'Group', 'value' => $party->group?->name],
                ['label' => 'Company', 'value' => $party->company?->name],
            ],
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:Consignor,Consignee,Both',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gst_number' => 'nullable|string|max:50',
        ]);

        if (empty($validated['company_id']) && auth()->user()?->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        }

        $validated['type'] = $validated['type'] ?? 'Both';

        $party = Party::create($validated);

        return response()->json([
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'gst_number' => $party->gst_number,
            ],
        ], 201);
    }

    public function edit(Party $party)
    {
        return Inertia::render('Parties/Edit', [
            'party' => $party,
            'companies' => Company::all(),
            'groups' => Group::all()
        ,
            'cities' => City::all(),
            'states' => State::all()]);
    }

    public function update(Request $request, Party $party)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'nullable|string',
            'type' => 'nullable|string',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string',
            'mobile_sms' => 'nullable|string',
            'email' => 'nullable|email',
            'gst_number' => 'nullable|string',
            'bilty_charge' => 'nullable|numeric'
        ]);

        $party->update($validated);
        return redirect()->route('parties.index')->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party)
    {
        $party->delete();
        return redirect()->route('parties.index')->with('success', 'Party deleted successfully.');
    }
}