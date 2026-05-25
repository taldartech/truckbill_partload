<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index()
    {
        return Inertia::render('Groups/Index', [
            'items' => Group::orderBy('id', 'desc')->paginate(10)
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

        Group::create($validated);

        return redirect()->back()->with('success', 'Groups created successfully.');
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group->update($validated);

        return redirect()->back()->with('success', 'Groups updated successfully.');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->back()->with('success', 'Groups deleted successfully.');
    }
}
