<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class GroupController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view groups', only: ['index', 'show']),
            new Middleware('permission:create groups', only: ['create', 'store']),
            new Middleware('permission:edit groups', only: ['edit', 'update']),
            new Middleware('permission:delete groups', only: ['destroy']),
        ];
    }
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
