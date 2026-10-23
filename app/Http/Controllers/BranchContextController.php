<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    public function select()
    {
        $user = auth()->user();

        if (! $user?->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('branches.index');
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

        // @codeCoverageIgnoreStart
        // Unreachable in practice: CompanyScope + findOrFail already 404s branches the user cannot see,
        // so a visible branch always belongs to this user's company and this 403 never fires.
        if ($branch->company?->user_id !== $user->id) {
            abort(403, 'You can only select branches from your registered companies.');
        }
        // @codeCoverageIgnoreEnd

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

        return redirect()->route('branches.index')->with('success', 'Branch context cleared.');
    }
}
