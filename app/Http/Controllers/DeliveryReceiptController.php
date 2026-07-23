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
use Illuminate\Validation\Rule;
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
        $branchGstRate = $this->branchGstRate();

        return Inertia::render('DeliveryReceipts/Create', [
            'parties' => Party::query()->orderBy('name')->get(['id', 'name']),
            'gr_sources' => $this->grSourceOptions(),
            'gst_rate' => $branchGstRate,
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
            ->with(['consignee:id,name', 'good:id,name', 'toStation:id,name'])
            ->find((int) $matches[1]);

        if (! $inward) {
            return response()->json(['message' => 'GR entry not found or not available for delivery.'], 404);
        }

        return response()->json($this->mapInwardForReceipt($inward));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['gst_rate'] = $this->branchGstRate();

        if (($validated['mode_of_payment'] ?? null) !== 'Due') {
            $validated['payment_due_from_id'] = null;
        }

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
        $branchGstRate = $this->branchGstRate();

        return Inertia::render('DeliveryReceipts/Edit', [
            'receipt' => $deliveryReceipt->load(['consignee:id,name', 'paymentDueFrom:id,name']),
            'parties' => Party::query()->orderBy('name')->get(['id', 'name']),
            'gr_sources' => $this->grSourceOptions($deliveryReceipt->id),
            'gst_rate' => $branchGstRate,
        ]);
    }

    public function update(Request $request, DeliveryReceipt $deliveryReceipt)
    {
        $validated = $request->validate($this->rules());
        $validated['gst_rate'] = $this->branchGstRate();

        if (($validated['mode_of_payment'] ?? null) !== 'Due') {
            $validated['payment_due_from_id'] = null;
        }

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
        $delivery_receipt->load(['consignee', 'paymentDueFrom']);
        $company = \App\Models\Company::find($delivery_receipt->company_id);

        return view('prints.delivery_receipt', ['receipt' => $delivery_receipt, 'company' => $company]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'delivery_receipt_no' => 'required|string|max:255',
            'date' => 'required|date',
            'gr_no_bilty_no' => 'required|string|max:255',
            'mode_of_payment' => ['required', 'string', Rule::in(['Cash', 'Bank', 'Due'])],
            'consignee_id' => 'required|exists:parties,id',
            'haste' => 'nullable|string|max:255',
            'payment_due_from_id' => [
                'nullable',
                Rule::requiredIf(fn () => request('mode_of_payment') === 'Due'),
                'exists:parties,id',
            ],
            'freight' => 'nullable|numeric|min:0',
            'handling_charge' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'stationery_charge' => 'nullable|numeric|min:0',
            'demurrage' => 'nullable|numeric|min:0',
            'other_charge' => 'nullable|numeric|min:0',
            'carriage_charge' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'gst_on' => ['nullable', 'string', Rule::in(['delivery_charge', 'total_charge'])],
            'gst' => 'nullable|numeric|min:0',
            'gst_pay_by' => ['nullable', 'string', Rule::in(['Consignor', 'Consignee', 'Transporter', 'Exempted'])],
            'less' => 'nullable|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
        ];
    }

    private function grSourceOptions(?int $exceptReceiptId = null): array
    {
        $usedNumbers = $this->usedGrNumbers($exceptReceiptId);

        return $this->deliverableInwardQuery()
            ->with('consignee:id,name')
            ->orderByDesc('id')
            ->get(['id', 'gr_no', 'consignee_id'])
            ->map(function (InwardRegisterDetail $detail) use ($usedNumbers) {
                $number = $this->inwardGrNumber($detail);
                if (in_array($number, $usedNumbers, true)) {
                    return null;
                }

                $consigneeName = $detail->consignee?->name ?: '—';

                return [
                    'id' => 'inward:'.$detail->id,
                    'label' => $number.' ('.$consigneeName.')',
                    'gr_no' => $number,
                    'consignee_name' => $consigneeName,
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
            'consignee_name' => $inward->consignee?->name ?? '—',
            'haste' => $inward->private_marka ?? '',
            'freight' => (float) ($inward->freight ?? 0),
            'handling_charge' => 0,
            'delivery_charge' => (float) ($inward->home_delivery_charge ?? 0),
            'stationery_charge' => 0,
            'demurrage' => 0,
            'other_charge' => 0,
            'carriage_charge' => 0,
            'packages' => (float) ($inward->no_of_packages ?? 0),
            'weight' => (float) ($inward->weight ?? 0),
            'item_name' => $inward->good?->name ?? '—',
            'to_station' => $inward->toStation?->name ?? '—',
            'gst_on' => 'total_charge',
            'gst_rate' => $this->branchGstRate(),
            'gst' => 0,
            'gst_pay_by' => 'Consignee',
            'less' => 0,
        ];
    }

    private function branchGstRate(): float
    {
        $branch = $this->activeBranch();

        if (! $branch || ! $branch->has_gst) {
            return 0.0;
        }

        return (float) ($branch->gst_slab ?? 0);
    }
}
