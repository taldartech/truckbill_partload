<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchContextController extends Controller
{
    public function select()
    {
        $context = app(TenantContext::class);
        $user = auth()->user();

        if (! $user?->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        $companyIds = $context->ownedCompanyIds();

        $branches = Branch::query()
            ->with('company:id,name')
            ->whereIn('company_id', $companyIds)
            ->orderBy('company_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('BranchContext/SelectBranch', [
            'branches' => $branches,
            'active_branch_id' => $context->sessionBranchId(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branch = Branch::with('company')->findOrFail($validated['branch_id']);

        if ($branch->company?->user_id !== $user->id) {
            abort(403, 'You can only select branches from your registered companies.');
        }

        session([
            'active_branch_id' => $branch->id,
            'active_company_id' => $branch->company_id,
        ]);

        return redirect()->route('dashboard')->with('success', 'Branch selected successfully.');
    }

    public function destroy()
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin()) {
            abort(403);
        }

        session()->forget(['active_branch_id', 'active_company_id']);

        return redirect()->route('branch-context.select')->with('success', 'Branch context cleared.');
    }
}
