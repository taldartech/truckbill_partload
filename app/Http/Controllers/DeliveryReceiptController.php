<?php

namespace App\Http\Controllers;

use App\Models\DeliveryReceipt;
use App\Models\InwardRegisterDetail;
use App\Models\Party;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\JsonResponse;
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
            new Middleware('permission:create delivery_receipts|edit delivery_receipts', only: ['lookupGr']),
            new Middleware('permission:delete delivery_receipts', only: ['destroy']),
        ];
    }

    public function index()
    {
        $receipts = DeliveryReceipt::with('consignee')->orderBy('id', 'desc')->paginate(10);

        return Inertia::render('DeliveryReceipts/Index', [
            'receipts' => $receipts,
        ]);
    }

    public function create()
    {
        return Inertia::render('DeliveryReceipts/Create', [
            'parties' => Party::all(),
            'gr_sources' => $this->grSourceOptions(),
        ]);
    }

    public function lookupGr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        if (! preg_match('/^inward:(\d+)$/', $validated['key'], $matches)) {
            return response()->json(['message' => 'Invalid GR reference.'], 422);
        }

        $inward = $this->deliverableInwardQuery()
            ->with('consignee')
            ->find((int) $matches[1]);

        if (! $inward) {
            return response()->json(['message' => 'GR entry not found or not available for delivery.'], 404);
        }

        return response()->json($this->mapInwardForReceipt($inward));
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
            'parties' => Party::all(),
            'gr_sources' => $this->grSourceOptions($deliveryReceipt->id),
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

    private function grSourceOptions(?int $exceptReceiptId = null): array
    {
        $usedNumbers = $this->usedGrNumbers($exceptReceiptId);

        return $this->deliverableInwardQuery()
            ->orderByDesc('id')
            ->get(['id', 'gr_no'])
            ->map(function (InwardRegisterDetail $detail) use ($usedNumbers) {
                $number = $this->inwardGrNumber($detail);
                if (in_array($number, $usedNumbers, true)) {
                    return null;
                }

                return [
                    'id' => 'inward:'.$detail->id,
                    'label' => $number,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function deliverableInwardQuery()
    {
        return InwardRegisterDetail::query()
            ->where('disposition', 'delivery')
            ->where('status', 'pending');
    }

    private function inwardGrNumber(InwardRegisterDetail $detail): string
    {
        return $detail->gr_no ?: 'GR-'.$detail->id;
    }

    private function usedGrNumbers(?int $exceptReceiptId = null): array
    {
        $query = DeliveryReceipt::query()->whereNotNull('gr_no_bilty_no');

        if ($exceptReceiptId) {
            $query->where('id', '!=', $exceptReceiptId);
        }

        return $query->pluck('gr_no_bilty_no')->filter()->values()->all();
    }

    private function mapInwardForReceipt(InwardRegisterDetail $inward): array
    {
        $number = $this->inwardGrNumber($inward);

        return [
            'gr_no_bilty_no' => $number,
            'consignee_id' => $inward->consignee_id,
            'haste' => $inward->private_marka ?? '',
            'mode_of_payment' => $this->mapPaymentType($inward->basis_of_booking),
            'payment_due_from' => '',
            'freight' => (float) ($inward->freight ?? 0),
            'handling_charge' => 0,
            'risk_dd_charge' => 0,
            'delivery_charge' => (float) ($inward->home_delivery_charge ?? 0),
            'stationary_charge' => 0,
            'demurrage' => 0,
            'other_charge' => 0,
            'cartage_charge' => 0,
            'gst_on' => '',
            'gst' => 0,
            'gst_pay_by' => '',
            'less' => 0,
        ];
    }

    private function mapPaymentType(?string $paymentType): string
    {
        $normalized = strtolower(trim((string) $paymentType));

        return match ($normalized) {
            'to pay', 'topay' => 'To Pay',
            'bank' => 'Bank',
            'paid' => 'Cash',
            default => $paymentType ? ucfirst($paymentType) : 'Cash',
        };
    }
}
