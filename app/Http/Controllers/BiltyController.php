<?php

namespace App\Http\Controllers;

use App\Http\Requests\BiltyRequest;
use App\Models\Bilty;
use App\Models\Company;
use App\Models\Party;
use App\Models\Station;
use App\Models\Good;
use App\Models\PackageType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class BiltyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view bilties', only: ['index', 'show']),
            new Middleware('permission:create bilties', only: ['create', 'store']),
            new Middleware('permission:edit bilties', only: ['edit', 'update']),
            new Middleware('permission:delete bilties', only: ['destroy']),
        ];
    }
    public function index()
    {
        $bilties = Bilty::with(['company', 'consignor', 'consignee', 'fromStation', 'toStation'])->latest()->paginate(10);
        return Inertia::render('Bilties/Index', [
            'bilties' => $bilties
        ]);
    }

    public function create()
    {
        $company_id = auth()->check() && auth()->user()->company_id ? auth()->user()->company_id : 1;
        $initial = \App\Models\Setting::where('company_id', $company_id)->where('key', 'bilty_initial')->value('value') ?? 'BLT-';
        $prefixLength = Str::length($initial);
        $maxSequence = \App\Models\Bilty::where('company_id', $company_id)
            ->where('bilty_number', 'like', $initial . '%')
            ->whereRaw('SUBSTRING(bilty_number, ?) REGEXP "^[0-9]+$"', [$prefixLength + 1])
            ->selectRaw('MAX(CAST(SUBSTRING(bilty_number, ?) AS UNSIGNED)) as max_seq', [$prefixLength + 1])
            ->value('max_seq') ?? 0;

        $nextNumber = $initial . str_pad($maxSequence + 1, 4, '0', STR_PAD_LEFT);
        $companyGstRates = $this->companyGstRates();

        return Inertia::render('Bilties/Create', [
            'companies' => Company::all(),
            'parties' => Party::all(),
            'stations' => Station::all(),
            'goods' => Good::all(),
            'package_types' => PackageType::all(),
            'next_bilty_number' => $nextNumber,
            'company_gst_rates' => $companyGstRates,
        ]);
    }

    public function store(BiltyRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? 'Pending';

        DB::transaction(function () use ($validated) {
            $details = $validated['details'] ?? [];
            unset($validated['details']);

            $bilty = Bilty::create($validated);

            foreach ($details as $item) {
                if (!empty($item['good_id'])) {
                    $bilty->biltyDetails()->create([
                        'company_id' => $bilty->company_id,
                        'good_id' => $item['good_id'],
                        'package_type_id' => $item['package_type_id'],
                        'no_of_packages' => $item['no_of_packages'],
                        'gross_weight' => $item['gross_weight'],
                        'charged_weight' => $item['charged_weight'] ?? null,
                        'rate' => $item['rate'],
                        'rate_type' => $item['rate_type'] ?? 'Fixed'
                    ]);
                }
            }
        });

        return redirect()->route('bilties.index')->with('success', 'Bilty created successfully.');
    }

    public function edit(Bilty $bilty)
    {
        $bilty->load('biltyDetails');
        return Inertia::render('Bilties/Edit', [
            'bilty' => $bilty,
            'companies' => Company::all(),
            'parties' => Party::all(),
            'stations' => Station::all(),
            'goods' => Good::all(),
            'package_types' => PackageType::all(),
            'company_gst_rates' => $this->companyGstRates(),
        ]);
    }

    public function update(BiltyRequest $request, Bilty $bilty)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $bilty) {
            $details = $validated['details'] ?? [];
            unset($validated['details']);

            $bilty->update($validated);
            $bilty->biltyDetails()->delete();

            foreach ($details as $item) {
                if (!empty($item['good_id'])) {
                    $bilty->biltyDetails()->create([
                        'company_id' => $bilty->company_id,
                        'good_id' => $item['good_id'],
                        'package_type_id' => $item['package_type_id'],
                        'no_of_packages' => $item['no_of_packages'],
                        'gross_weight' => $item['gross_weight'],
                        'charged_weight' => $item['charged_weight'] ?? null,
                        'rate' => $item['rate'],
                        'rate_type' => $item['rate_type'] ?? 'Fixed'
                    ]);
                }
            }
        });

        return redirect()->route('bilties.index')->with('success', 'Bilty updated successfully.');
    }

    public function destroy(Bilty $bilty)
    {
        $bilty->delete();
        return redirect()->route('bilties.index')->with('success', 'Bilty deleted successfully.');
    }

    public function print(Bilty $bilty)
    {
        $bilty->load(['fromStation', 'toStation', 'consignor', 'consignee', 'biltyDetails.good', 'biltyDetails.packageType', 'ewayBills']);
        $company = \App\Models\Company::find($bilty->company_id);
        
        return view('prints.bilty', compact('bilty', 'company'));
    }

    private function companyGstRates(): array
    {
        return \App\Models\Setting::where('key', 'default_gst_percent')
            ->pluck('value', 'company_id')
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }
}