<?php

namespace App\Http\Controllers;

use App\Models\PackageType;
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
            new Middleware('permission:create package_types', only: ['create', 'store']),
            new Middleware('permission:edit package_types', only: ['edit', 'update']),
            new Middleware('permission:delete package_types', only: ['destroy']),
        ];
    }
    public function index()
    {
        return Inertia::render('PackageTypes/Index', [
            'items' => PackageType::orderBy('id', 'desc')->paginate(10)
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
        return redirect()->back()->with('success', 'Package Types deleted successfully.');
    }
}
