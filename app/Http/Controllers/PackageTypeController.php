<?php

namespace App\Http\Controllers;

use App\Models\PackageType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PackageTypeController extends Controller
{
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

        if (auth()->check() && auth()->user()->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        }

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
