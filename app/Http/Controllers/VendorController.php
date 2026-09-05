<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view vendors', only: ['index', 'show']),
            new Middleware('permission:create vendors', only: ['create', 'store', 'quickStore', 'quickFormData']),
            new Middleware('permission:edit vendors', only: ['edit', 'update']),
            new Middleware('permission:delete vendors', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $vendors = Vendor::query()
            ->with('company')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%')
                        ->orWhere('gst_number', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'state_id']),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['state', 'city', 'company']);

        return Inertia::render('Vendors/Show', [
            'vendor' => $vendor,
        ]);
    }

    public function create()
    {
        return redirect()->route('vendors.index');
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->vendorRules(),
            $this->vendorMessages(),
        ));

        Vendor::create($validated);

        return redirect()->back()->with('success', 'Vendor created successfully.');
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
            $this->vendorRules(),
            $this->vendorMessages(),
        ));

        $vendor = Vendor::create($validated);

        return response()->json([
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
            ],
        ], 201);
    }

    public function edit(Vendor $vendor)
    {
        return redirect()->route('vendors.index');
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $this->stripTenantFields($request->validate(
            $this->vendorRules(),
            $this->vendorMessages(),
        ));

        $vendor->update($validated);

        return redirect()->back()->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    /**
     * @return array<string, string>
     */
    private function vendorRules(): array
    {
        return [
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
    private function vendorMessages(): array
    {
        return [
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'mobile.regex' => 'Mobile number must be a valid 10-digit Indian mobile number.',
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
        ];
    }
}
