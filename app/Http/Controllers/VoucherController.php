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
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'voucher_type' => ['nullable', 'in:Payment,Receipt,Journal,Contra'],
        ]);

        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->endOfMonth()->toDateString();
        $search = trim((string) ($validated['search'] ?? ''));

        $vouchers = Voucher::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('voucher_number', 'like', '%'.$search.'%')
                        ->orWhere('account_head', 'like', '%'.$search.'%')
                        ->orWhere('remark', 'like', '%'.$search.'%');
                });
            })
            ->when($fromDate, fn ($query) => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('date', '<=', $toDate))
            ->when(! empty($validated['voucher_type']), function ($query) use ($validated) {
                $query->where('voucher_type', $validated['voucher_type']);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'filters' => [
                'search' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'voucher_type' => $validated['voucher_type'] ?? '',
            ],
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
