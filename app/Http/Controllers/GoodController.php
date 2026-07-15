<?php

namespace App\Http\Controllers;

use App\Models\Good;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class GoodController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view goods', only: ['index', 'show']),
            new Middleware('permission:create goods', only: ['create', 'store', 'quickStore']),
            new Middleware('permission:edit goods', only: ['edit', 'update']),
            new Middleware('permission:delete goods', only: ['destroy']),
        ];
    }

    public function index()
    {
        return Inertia::render('Goods/Index', [
            'items' => Good::orderBy('id', 'desc')->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Good::create($validated);

        return redirect()->back()->with('success', 'Goods created successfully.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $this->stripTenantFields($request->validate([
            'name' => 'required|string|max:255',
        ]));

        $good = Good::create($validated);

        return response()->json([
            'good' => [
                'id' => $good->id,
                'name' => $good->name,
            ],
        ], 201);
    }

    public function update(Request $request, Good $good)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $good->update($validated);

        return redirect()->back()->with('success', 'Goods updated successfully.');
    }

    public function destroy(Good $good)
    {
        $good->delete();

        return redirect()->back()->with('success', 'Goods deleted successfully.');
    }
}
