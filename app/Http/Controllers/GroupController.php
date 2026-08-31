<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GroupController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view groups', only: ['index', 'show']),
            new Middleware('permission:create groups', only: ['create', 'store', 'quickStore']),
            new Middleware('permission:edit groups', only: ['edit', 'update']),
            new Middleware('permission:delete groups', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $items = Group::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Groups/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
            ],
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

    public function show(Group $group)
    {
        return Inertia::render('Groups/Show', [
            'group' => $group,
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
        ]));

        $group = Group::create($validated);

        return response()->json([
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
        ], 201);
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
        $group->update([
            'delete_remark' => request('delete_remark'),
            'deleted_by' => Auth::id(),
        ]);
        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Groups deleted successfully.');
    }
}
