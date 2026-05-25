<?php

namespace App\Http\Controllers;

use App\Models\Good;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoodController extends Controller
{
    public function index()
    {
        return Inertia::render('Goods/Index', [
            'items' => Good::orderBy('id', 'desc')->paginate(10)
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

        Good::create($validated);

        return redirect()->back()->with('success', 'Goods created successfully.');
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
