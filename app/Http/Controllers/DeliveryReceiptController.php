<?php

namespace App\Http\Controllers;

use App\Models\DeliveryReceipt;
use App\Models\Party;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class DeliveryReceiptController extends Controller implements HasMiddleware
{
    use RedirectsWithOptionalPrint;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view delivery_receipts', only: ['index', 'show', 'print']),
            new Middleware('permission:create delivery_receipts', only: ['create', 'store']),
            new Middleware('permission:edit delivery_receipts', only: ['edit', 'update']),
            new Middleware('permission:delete delivery_receipts', only: ['destroy']),
        ];
    }
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

        $receipt = DeliveryReceipt::create($validated);

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'delivery-receipts.index',
            'delivery-receipts.print',
            $receipt,
            'Delivery Receipt created successfully.',
        );
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

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'delivery-receipts.index',
            'delivery-receipts.print',
            $deliveryReceipt,
            'Delivery Receipt updated successfully.',
        );
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
