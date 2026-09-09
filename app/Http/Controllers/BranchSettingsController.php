<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class BranchSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view branches', only: ['edit']),
            new Middleware('permission:edit branches', only: ['update']),
        ];
    }

    public function edit(Branch $branch)
    {
        $this->authorizeBranch($branch);

        $settings = $branch->settings ?? $branch->settings()->create([
            'bilty_print_config' => BranchSetting::defaultBiltyPrintConfig(),
        ]);

        return Inertia::render('Branches/Settings', [
            'branch' => $branch->only(['id', 'name']),
            'settings' => [
                'bilty_initial' => $settings->bilty_initial ?? '',
                'challan_initial' => $settings->challan_initial ?? '',
                'main_challan_initial' => $settings->main_challan_initial ?? '',
                'invoice_initial' => $settings->invoice_initial ?? '',
                'inward_register_initial' => $settings->inward_register_initial ?? '',
                'voucher_initial' => $settings->voucher_initial ?? '',
                'delivery_initial' => $settings->delivery_initial ?? '',
                'bilty_print_config' => $settings->resolvedBiltyPrintConfig(),
            ],
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeBranch($branch);

        $validated = $request->validate([
            'bilty_initial' => 'nullable|string|max:20',
            'challan_initial' => 'nullable|string|max:20',
            'main_challan_initial' => 'nullable|string|max:20',
            'invoice_initial' => 'nullable|string|max:20',
            'inward_register_initial' => 'nullable|string|max:20',
            'voucher_initial' => 'nullable|string|max:20',
            'delivery_initial' => 'nullable|string|max:20',
            ...BranchSetting::biltyPrintConfigValidationRules(),
        ]);

        foreach (['bilty_initial', 'challan_initial', 'main_challan_initial', 'invoice_initial', 'inward_register_initial', 'voucher_initial', 'delivery_initial'] as $field) {
            $value = trim((string) ($validated[$field] ?? ''));
            $validated[$field] = $value !== '' ? $value : null;
        }

        $validated['bilty_print_config'] = BranchSetting::normalizeBiltyPrintConfig($validated['bilty_print_config'] ?? null);

        $branch->settings()->updateOrCreate(
            ['branch_id' => $branch->id],
            $validated
        );

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch settings updated successfully.');
    }

    private function authorizeBranch(Branch $branch): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            $company = Company::find($branch->company_id);

            if (! $company || (int) $company->user_id !== (int) auth()->id()) {
                abort(403);
            }
        }
    }
}
