<?php

namespace App\Http\Controllers;

use App\Models\PackageType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class PackageTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view package_types', only: ['index', 'show']),
            new Middleware('permission:create package_types', only: ['create', 'store', 'quickStore']),
            new Middleware('permission:edit package_types', only: ['edit', 'update']),
            new Middleware('permission:delete package_types', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $items = PackageType::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PackageTypes/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        PackageType::create($validated);

        return redirect()->back()->with('success', 'Package Types created successfully.');
    }

    public function show(PackageType $package_type)
    {
        return Inertia::render('PackageTypes/Show', [
            'packageType' => $package_type,
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
        ]));

        $packageType = PackageType::create($validated);

        return response()->json([
            'package_type' => [
                'id' => $packageType->id,
                'name' => $packageType->name,
            ],
        ], 201);
    }

    public function update(Request $request, PackageType $package_type)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $package_type->update($validated);

        return redirect()->back()->with('success', 'Package Types updated successfully.');
    }

    public function destroy(PackageType $package_type)
    {
        $package_type->delete();

        return redirect()->route('package-types.index')->with('success', 'Package Types deleted successfully.');
    }
}
