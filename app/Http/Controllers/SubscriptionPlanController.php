<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class SubscriptionPlanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view subscriptions', only: ['index', 'show']),
            new Middleware('permission:create subscriptions', only: ['create', 'store']),
            new Middleware('permission:edit subscriptions', only: ['edit', 'update']),
            new Middleware('permission:delete subscriptions', only: ['destroy']),
        ];
    }

    public function index()
    {
        return Inertia::render('Subscriptions/Index', [
            'plans' => SubscriptionPlan::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_users' => 'required|integer|min:1',
            'max_branches' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
        ]);

        SubscriptionPlan::create($validated);
        return redirect()->route('subscriptions.index')->with('success', 'Plan created successfully.');
    }

    public function update(Request $request, SubscriptionPlan $subscription)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_users' => 'required|integer|min:1',
            'max_branches' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
        ]);

        $subscription->update($validated);
        return redirect()->route('subscriptions.index')->with('success', 'Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $subscription)
    {
        $subscription->delete();
        return redirect()->route('subscriptions.index')->with('success', 'Plan deleted successfully.');
    }
}
