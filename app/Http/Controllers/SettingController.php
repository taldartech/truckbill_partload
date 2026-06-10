<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdminSettings();

        $companyId = $this->companyId();
        $branchId = $this->branchId();

        $settings = Setting::query()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->pluck('value', 'key')
            ->toArray();

        $defaultSettings = [
            'invoice_prefix' => $settings['invoice_prefix'] ?? 'INV-',
            'default_gst_percent' => $settings['default_gst_percent'] ?? '18',
            'bilty_terms' => $settings['bilty_terms'] ?? '1. All goods at owner risk. 2. Subject to local jurisdiction.',
            'invoice_terms' => $settings['invoice_terms'] ?? '1. Payment within 15 days. 2. Interest @24% p.a. for delayed payments.',
            'logo' => $settings['logo'] ?? null,
            'bilty_initial' => $settings['bilty_initial'] ?? '',
            'challan_initial' => $settings['challan_initial'] ?? '',
            'main_challan_initial' => $settings['main_challan_initial'] ?? '',
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $defaultSettings,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdminSettings();

        $companyId = $this->companyId();
        $branchId = $this->branchId();

        $validated = $request->validate([
            'invoice_prefix' => 'nullable|string',
            'default_gst_percent' => 'nullable|numeric',
            'bilty_terms' => 'nullable|string',
            'invoice_terms' => 'nullable|string',
            'bilty_initial' => 'nullable|string',
            'challan_initial' => 'nullable|string',
            'main_challan_initial' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company_logos', 'public');
            $validated['logo'] = '/storage/'.$path;
        }

        foreach ($validated as $key => $value) {
            if ($value !== null || ($key === 'logo' && ! isset($validated['logo']))) {
                if ($key === 'logo' && ! isset($validated['logo'])) {
                    continue;
                }
                Setting::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'key' => $key,
                    ],
                    ['value' => $value]
                );
            }
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    private function authorizeSuperAdminSettings(): void
    {
        if (! $this->tenant()->isSuperAdmin() || ! $this->tenant()->isActingAsAdmin()) {
            abort(403, 'Global Settings are only accessible to Super Admins.');
        }
    }
}
