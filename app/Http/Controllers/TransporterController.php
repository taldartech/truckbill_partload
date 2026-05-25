<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class TransporterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view transporters', only: ['index', 'show']),
            new Middleware('permission:create transporters', only: ['create', 'store']),
            new Middleware('permission:edit transporters', only: ['edit', 'update']),
            new Middleware('permission:delete transporters', only: ['destroy']),
        ];
    }

    public function index()
    {
        $transporters = Transporter::with('company')->latest()->paginate(10);
        return Inertia::render('Transporters/Index', [
            'transporters' => $transporters
        ]);
    }

    public function create()
    {
        return Inertia::render('Transporters/Create', [
            'companies' => Company::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string',
            'short_name' => 'nullable|string',
            'gst_number' => 'nullable|string',
            'pan_number' => 'nullable|string'
        ]);

        Transporter::create($validated);
        return redirect()->route('transporters.index')->with('success', 'Transporter created successfully.');
    }

    public function edit(Transporter $transporter)
    {
        return Inertia::render('Transporters/Edit', [
            'transporter' => $transporter,
            'companies' => Company::all()
        ]);
    }

    public function update(Request $request, Transporter $transporter)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string',
            'short_name' => 'nullable|string',
            'gst_number' => 'nullable|string',
            'pan_number' => 'nullable|string'
        ]);

        $transporter->update($validated);
        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully.');
    }

    public function destroy(Transporter $transporter)
    {
        $transporter->delete();
        return redirect()->route('transporters.index')->with('success', 'Transporter deleted successfully.');
    }
}