<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class VoucherController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view vouchers', only: ['index', 'show']),
            new Middleware('permission:create vouchers', only: ['create', 'store']),
            new Middleware('permission:edit vouchers', only: ['edit', 'update']),
            new Middleware('permission:delete vouchers', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Vouchers/Index', [
            'vouchers' => Voucher::orderBy('id', 'desc')->paginate(10)
        ]);
    }

    public function create()
    {
        return Inertia::render('Vouchers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'voucher_number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'voucher_type' => 'required|in:Payment,Receipt,Journal,Contra',
            'account_head' => 'required|string|max:255',
            'debit_credit' => 'required|in:Dr,Cr',
            'amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string'
        ]);

        Voucher::create($validated);

        return redirect()->route('vouchers.index')->with('success', 'Voucher created successfully.');
    }

    public function edit(Voucher $voucher)
    {
        return Inertia::render('Vouchers/Edit', [
            'voucher' => $voucher
        ]);
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'voucher_number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'voucher_type' => 'required|in:Payment,Receipt,Journal,Contra',
            'account_head' => 'required|string|max:255',
            'debit_credit' => 'required|in:Dr,Cr',
            'amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string'
        ]);

        $voucher->update($validated);

        return redirect()->route('vouchers.index')->with('success', 'Voucher updated successfully.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->route('vouchers.index')->with('success', 'Voucher deleted successfully.');
    }
}
