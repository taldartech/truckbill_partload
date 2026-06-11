<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Party;
use App\Models\Bilty;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class InvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view invoices', only: ['index', 'show', 'print']),
            new Middleware('permission:create invoices', only: ['create', 'store']),
            new Middleware('permission:edit invoices', only: ['edit', 'update']),
            new Middleware('permission:delete invoices', only: ['destroy']),
        ];
    }
    public function index()
    {
        $invoices = Invoice::with('party')->orderBy('id', 'desc')->paginate(10);
        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices
        ]);
    }

    public function create()
    {
        $invoicedBiltyIds = InvoiceDetail::pluck('bilty_id')->toArray();
        $pendingBilties = Bilty::whereNotIn('id', $invoicedBiltyIds)->with(['fromStation', 'toStation'])->get();

        return Inertia::render('Invoices/Create', [
            'parties' => Party::all(),
            'bilties' => $pendingBilties
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => 'required|string|unique:invoices',
            'date' => 'required|date',
            'party_id' => 'required|exists:parties,id',
            'status' => 'required|string',
            'remark' => 'nullable|string',
            'bilty_ids' => 'required|array',
            'bilty_ids.*' => 'exists:bilties,id'
        ]);

        $bilties = Bilty::whereIn('id', $request->bilty_ids)->get();
        $totalAmount = $bilties->sum('amount');

        $invoice = Invoice::create([
            'invoice_no' => $validated['invoice_no'],
            'date' => $validated['date'],
            'party_id' => $validated['party_id'],
            'status' => $validated['status'],
            'remark' => $validated['remark'],
            'total_amount' => $totalAmount,
            'gst_amount' => 0,
            'net_amount' => $totalAmount,
        ]);

        foreach ($bilties as $bilty) {
            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'bilty_id' => $bilty->id,
                'amount' => $bilty->amount
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        //
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['party', 'details.bilty.fromStation', 'details.bilty.toStation']);
        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'parties' => Party::all()
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'party_id' => 'required|exists:parties,id',
            'status' => 'required|string',
            'remark' => 'nullable|string'
        ]);

        $invoice->update($validated);
        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['party', 'details.bilty.fromStation', 'details.bilty.toStation', 'branch']);
        $company = \App\Models\Company::find($invoice->company_id);
        $branch = $invoice->branch;

        return view('prints.invoice', compact('invoice', 'company', 'branch'));
    }
}
