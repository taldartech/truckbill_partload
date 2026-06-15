<?php

namespace App\Http\Controllers;

use App\Models\Party;
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
            'parties' => $parties,
        ]);
    }

    public function create()
    {
        return Inertia::render('Parties/Create', [
            'groups' => Group::all(),
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:Consignor,Consignee,Both',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'mobile_sms' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'bilty_charge' => 'nullable|numeric|min:0',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'mobile_sms.digits' => 'SMS mobile number must be exactly 10 digits.',
            'mobile_sms.regex' => 'SMS mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
        ]));

        Party::create($validated);

        return redirect()->route('parties.index')->with('success', 'Party created successfully.');
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
                ['label' => 'Type', 'value' => $party->type],
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
        $validated = $this->stripTenantFields($request->validate([
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:Consignor,Consignee,Both',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
        ]));

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
            'groups' => Group::all(),
            'cities' => City::all(),
            'states' => State::all(),
        ]);
    }

    public function update(Request $request, Party $party)
    {
        $validated = $this->stripTenantFields($request->validate([
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:Consignor,Consignee,Both',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'mobile' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'mobile_sms' => 'nullable|string|digits:10|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|string|email|max:255',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'bilty_charge' => 'nullable|numeric|min:0',
        ], [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'mobile_sms.digits' => 'SMS mobile number must be exactly 10 digits.',
            'mobile_sms.regex' => 'SMS mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
        ]));

        $party->update($validated);

        return redirect()->route('parties.index')->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party)
    {
        $party->delete();

        return redirect()->route('parties.index')->with('success', 'Party deleted successfully.');
    }
}
