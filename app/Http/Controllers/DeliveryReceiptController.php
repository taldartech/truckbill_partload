<?php

namespace App\Http\Controllers;

use App\Models\DeliveryReceipt;
use App\Models\Party;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $receipts = DeliveryReceipt::with('consignee')->orderBy('id', 'desc')->paginate(10);
        return Inertia::render('DeliveryReceipts/Index', [
            'receipts' => $receipts
        ]);
    }

    public function create()
    {
        return Inertia::render('DeliveryReceipts/Create', [
            'parties' => Party::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_receipt_no' => 'nullable|string|max:255',
            'date' => 'required|date',
            'gr_no_bilty_no' => 'required|string|max:255',
            'mode_of_payment' => 'nullable|string|max:255',
            'office_name' => 'nullable|string|max:255',
            'consignee_id' => 'required|exists:parties,id',
            'haste' => 'nullable|string|max:255',
            'payment_due_from' => 'nullable|string|max:255',
            'freight' => 'nullable|numeric|min:0',
            'handling_charge' => 'nullable|numeric|min:0',
            'risk_dd_charge' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'stationary_charge' => 'nullable|numeric|min:0',
            'demurrage' => 'nullable|numeric|min:0',
            'other_charge' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'gst_on' => 'nullable|string|max:255',
            'gst' => 'nullable|numeric|min:0',
            'gst_pay_by' => 'nullable|string|max:255',
            'less' => 'nullable|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'cartage_charge' => 'nullable|numeric|min:0',
        ]);

        if (auth()->check() && auth()->user()->company_id) {
            $validated['company_id'] = auth()->user()->company_id;
        } else {
            $validated['company_id'] = 1;
        }

        DeliveryReceipt::create($validated);

        return redirect()->route('delivery-receipts.index')->with('success', 'Delivery Receipt created successfully.');
    }

    public function edit(DeliveryReceipt $deliveryReceipt)
    {
        return Inertia::render('DeliveryReceipts/Edit', [
            'receipt' => $deliveryReceipt,
            'parties' => Party::all()
        ]);
    }

    public function update(Request $request, DeliveryReceipt $deliveryReceipt)
    {
        $validated = $request->validate([
            'delivery_receipt_no' => 'nullable|string|max:255',
            'date' => 'required|date',
            'gr_no_bilty_no' => 'required|string|max:255',
            'mode_of_payment' => 'nullable|string|max:255',
            'office_name' => 'nullable|string|max:255',
            'consignee_id' => 'required|exists:parties,id',
            'haste' => 'nullable|string|max:255',
            'payment_due_from' => 'nullable|string|max:255',
            'freight' => 'nullable|numeric|min:0',
            'handling_charge' => 'nullable|numeric|min:0',
            'risk_dd_charge' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'stationary_charge' => 'nullable|numeric|min:0',
            'demurrage' => 'nullable|numeric|min:0',
            'other_charge' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'gst_on' => 'nullable|string|max:255',
            'gst' => 'nullable|numeric|min:0',
            'gst_pay_by' => 'nullable|string|max:255',
            'less' => 'nullable|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'cartage_charge' => 'nullable|numeric|min:0',
        ]);

        $deliveryReceipt->update($validated);

        return redirect()->route('delivery-receipts.index')->with('success', 'Delivery Receipt updated successfully.');
    }

    public function destroy(DeliveryReceipt $deliveryReceipt)
    {
        $deliveryReceipt->delete();
        return redirect()->route('delivery-receipts.index')->with('success', 'Delivery Receipt deleted successfully.');
    }

    public function print(DeliveryReceipt $delivery_receipt)
    {
        $delivery_receipt->load('consignee');
        $company = \App\Models\Company::find($delivery_receipt->company_id);
        
        return view('prints.delivery_receipt', ['receipt' => $delivery_receipt, 'company' => $company]);
    }
}
