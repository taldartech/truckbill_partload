<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
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
            'transporters' => $transporters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Transporters/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'pan_number' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
        ], [
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
            'pan_number.size' => 'PAN must be exactly 10 characters.',
            'pan_number.regex' => 'PAN format is invalid.',
        ]));

        Transporter::create($validated);

        return redirect()->route('transporters.index')->with('success', 'Transporter created successfully.');
    }

    public function edit(Transporter $transporter)
    {
        return Inertia::render('Transporters/Edit', [
            'transporter' => $transporter,
        ]);
    }

    public function update(Request $request, Transporter $transporter)
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'gst_number' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            'pan_number' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
        ], [
            'gst_number.size' => 'GST number must be exactly 15 characters.',
            'gst_number.regex' => 'GST number format is invalid.',
            'pan_number.size' => 'PAN must be exactly 10 characters.',
            'pan_number.regex' => 'PAN format is invalid.',
        ]));

        $transporter->update($validated);

        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully.');
    }

    public function destroy(Transporter $transporter)
    {
        $transporter->delete();

        return redirect()->route('transporters.index')->with('success', 'Transporter deleted successfully.');
    }
}
