<?php

namespace App\Http\Controllers;

use App\Models\TruckType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class TruckTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view truck_types', only: ['index', 'show']),
            new Middleware('permission:create truck_types', only: ['create', 'store', 'quickStore']),
            new Middleware('permission:edit truck_types', only: ['edit', 'update']),
            new Middleware('permission:delete truck_types', only: ['destroy']),
        ];
    }

    public function index()
    {
        return Inertia::render('TruckTypes/Index', [
            'truckTypes' => TruckType::orderBy('id', 'desc')->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        TruckType::create($validated);

        return redirect()->back()->with('success', 'Truck type created successfully.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
        ]));

        $truckType = TruckType::create($validated);

        return response()->json([
            'truck_type' => [
                'id' => $truckType->id,
                'name' => $truckType->name,
            ],
        ], 201);
    }

    public function update(Request $request, TruckType $truckType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $truckType->update($validated);

        return redirect()->back()->with('success', 'Truck type updated successfully.');
    }

    public function destroy(TruckType $truckType)
    {
        $truckType->delete();

        return redirect()->back()->with('success', 'Truck type deleted successfully.');
    }
}
