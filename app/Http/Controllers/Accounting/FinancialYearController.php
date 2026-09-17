<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Services\Accounting\FinancialYearService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinancialYearController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounting_settings', only: ['index']),
            new Middleware('permission:create accounting_settings', only: ['store']),
            new Middleware('permission:edit accounting_settings', only: ['update', 'setCurrent']),
        ];
    }

    public function index(FinancialYearService $service)
    {
        $companyId = (int) ($this->tenant()->activeCompanyId() ?? 0);
        if ($companyId > 0) {
            $service->ensureCurrent($companyId);
        }

        return Inertia::render('Accounting/FinancialYears/Index', [
            'financialYears' => FinancialYear::query()->orderByDesc('starts_on')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            if ($validated['is_current'] ?? false) {
                FinancialYear::query()->where('is_current', true)->update(['is_current' => false]);
            }

            FinancialYear::query()->create([
                ...$validated,
                'is_current' => $validated['is_current'] ?? false,
                'is_closed' => false,
            ]);
        });

        return redirect()->back()->with('success', 'Financial year created.');
    }

    public function update(Request $request, FinancialYear $financialYear)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_closed' => ['boolean'],
            'is_current' => ['boolean'],
        ]);

        DB::transaction(function () use ($financialYear, $validated) {
            if ($validated['is_current'] ?? false) {
                FinancialYear::query()
                    ->where('company_id', $financialYear->company_id)
                    ->where('is_current', true)
                    ->whereKeyNot($financialYear->id)
                    ->update(['is_current' => false]);
            }

            $financialYear->update($validated);
        });

        return redirect()->back()->with('success', 'Financial year updated.');
    }

    public function setCurrent(FinancialYear $financialYear)
    {
        DB::transaction(function () use ($financialYear) {
            FinancialYear::query()
                ->where('company_id', $financialYear->company_id)
                ->update(['is_current' => false]);

            $financialYear->update(['is_current' => true, 'is_closed' => false]);
        });

        return redirect()->back()->with('success', 'Current financial year updated.');
    }
}
