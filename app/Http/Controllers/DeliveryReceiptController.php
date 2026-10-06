<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesAccountingPanel;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DeliveryReceipt;
use App\Models\InwardRegisterDetail;
use App\Models\Party;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Support\DocumentNumber;
use App\Support\GstCalculator;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DeliveryReceiptController extends Controller implements HasMiddleware
{
    use RedirectsWithOptionalPrint;
    use SharesAccountingPanel;

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

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'consignee_id' => ['nullable', 'integer', 'exists:parties,id'],
        ]);

        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->endOfMonth()->toDateString();
        $search = trim((string) ($validated['search'] ?? ''));

        $receipts = DeliveryReceipt::query()
            ->with('consignee')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('delivery_receipt_no', 'like', '%'.$search.'%')
                        ->orWhere('gr_no_bilty_no', 'like', '%'.$search.'%');
                });
            })
            ->when($fromDate, fn ($query) => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('date', '<=', $toDate))
            ->when(! empty($validated['consignee_id']), function ($query) use ($validated) {
                $query->where('consignee_id', $validated['consignee_id']);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('DeliveryReceipts/Index', [
            'receipts' => $receipts,
            'parties' => Party::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'consignee_id' => isset($validated['consignee_id']) ? (string) $validated['consignee_id'] : '',
            ],
        ]);
    }

    public function create()
    {
        $branchGstRate = $this->branchGstRate();
        $branch = $this->activeBranch();
        $nextNumber = DocumentNumber::next(
            DeliveryReceipt::query(),
            'delivery_receipt_no',
            $branch?->settings?->delivery_initial
        );

        return Inertia::render('DeliveryReceipts/Create', [
            'parties' => Party::query()->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gr_sources' => $this->grSourceOptions(),
            'gst_rate' => $branchGstRate,
            'gst_exemption_limit' => $this->gstExemptionLimit(),
            'next_delivery_receipt_no' => $nextNumber,
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

    public function store(Request $request, OperationalAccountingBridge $bridge)
    {
        $validated = $this->normalizePayment($request->validate($this->rules()));
        $validated['gst_rate'] = $this->branchGstRate();

        $receipt = DB::transaction(function () use ($validated, $bridge) {
            $receipt = DeliveryReceipt::create($validated);
            $bridge->sync($receipt);

            return $receipt;
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'delivery-receipts.index',
            'delivery-receipts.print',
            $receipt,
            'Delivery Receipt created successfully.',
        );
    }

    public function show(DeliveryReceipt $deliveryReceipt)
    {
        $deliveryReceipt->load([
            'consignee.state',
            'consignee.city',
            'paymentDueFrom.state',
            'paymentDueFrom.city',
        ]);

        return Inertia::render('DeliveryReceipts/Show', [
            'receipt' => $deliveryReceipt,
            'gr_source' => $this->resolveGrSource($deliveryReceipt->gr_no_bilty_no),
            'accounting' => $this->accountingPanel($deliveryReceipt),
        ]);
    }

    public function edit(DeliveryReceipt $deliveryReceipt)
    {
        $branchGstRate = $this->branchGstRate();

        return Inertia::render('DeliveryReceipts/Edit', [
            'receipt' => $deliveryReceipt->load(['consignee:id,name', 'paymentDueFrom:id,name']),
            'parties' => Party::query()->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gr_sources' => $this->grSourceOptions($deliveryReceipt->id),
            'gst_rate' => $branchGstRate,
            'gst_exemption_limit' => $this->gstExemptionLimit(),
        ]);
    }

    public function update(Request $request, DeliveryReceipt $deliveryReceipt, OperationalAccountingBridge $bridge)
    {
        $validated = $this->normalizePayment($request->validate($this->rules()));
        $validated['gst_rate'] = $this->branchGstRate();

        DB::transaction(function () use ($validated, $deliveryReceipt, $bridge) {
            $deliveryReceipt->update($validated);
            $bridge->sync($deliveryReceipt);
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'delivery-receipts.index',
            'delivery-receipts.print',
            $deliveryReceipt,
            'Delivery Receipt updated successfully.',
        );
    }

    public function destroy(Request $request, DeliveryReceipt $deliveryReceipt, OperationalAccountingBridge $bridge)
    {
        DB::transaction(function () use ($request, $deliveryReceipt, $bridge) {
            $bridge->clear($deliveryReceipt);
            $deliveryReceipt->update([
                'delete_remark' => $request->delete_remark,
                'deleted_by' => Auth::id(),
            ]);

            $deliveryReceipt->delete();
        });

        return redirect()
            ->route('delivery-receipts.index')
            ->with('success', 'Delivery Receipt deleted successfully.');
    }

    public function print(DeliveryReceipt $delivery_receipt)
    {
        $delivery_receipt->load([
            'consignee.city',
            'consignee.state',
            'paymentDueFrom',
            'branch.company',
            'branch.city',
            'branch.state',
        ]);

        $branch = $delivery_receipt->branch;
        $company = $branch?->company ?? Company::find($delivery_receipt->company_id);
        $grSource = $this->formatGrSourceForPrint(
            $this->resolveGrSource($delivery_receipt->gr_no_bilty_no)
        );

        return view('prints.delivery_receipt', [
            'receipt' => $delivery_receipt,
            'company' => $company,
            'branch' => $branch,
            'grSource' => $grSource,
        ]);
    }

    /**
     * @return array{
     *     consignor: ?string,
     *     from_station: ?string,
     *     to_station: ?string,
     *     goods_name: ?string,
     *     private_marka: ?string,
     *     packages: float|int|null,
     *     weight: float|int|null,
     *     gr_date: ?string,
     *     received_date: ?string,
     *     basis_of_booking: ?string,
     *     freight: float|int|null,
     *     home_delivery_charge: float|int|null,
     *     disposition: ?string,
     *     status: ?string
     * }
     */
    private function resolveGrSource(?string $grNo): array
    {
        $empty = [
            'consignor' => null,
            'from_station' => null,
            'to_station' => null,
            'goods_name' => null,
            'private_marka' => null,
            'packages' => null,
            'weight' => null,
            'gr_date' => null,
            'received_date' => null,
            'basis_of_booking' => null,
            'freight' => null,
            'home_delivery_charge' => null,
            'disposition' => null,
            'status' => null,
        ];

        $grNo = trim((string) $grNo);
        if ($grNo === '') {
            return $empty;
        }

        $detail = InwardRegisterDetail::query()
            ->with([
                'consignor:id,name',
                'fromStation:id,name',
                'toStation:id,name',
                'good:id,name',
                'inwardRegister:id,date',
            ])
            ->where(function ($query) use ($grNo) {
                $query->where('gr_no', $grNo);

                if (preg_match('/^GR-(\d+)$/i', $grNo, $matches)) {
                    $query->orWhere('id', (int) $matches[1]);
                }
            })
            ->orderByDesc('id')
            ->first();

        if (! $detail) {
            return $empty;
        }

        return [
            'consignor' => $detail->consignor?->name,
            'from_station' => $detail->fromStation?->name,
            'to_station' => $detail->toStation?->name,
            'goods_name' => $detail->good?->name,
            'private_marka' => $detail->private_marka,
            'packages' => $detail->no_of_packages,
            'weight' => $detail->weight,
            'gr_date' => $detail->date ? date('Y-m-d', strtotime((string) $detail->date)) : null,
            'received_date' => $detail->inwardRegister?->date
                ? date('Y-m-d', strtotime((string) $detail->inwardRegister->date))
                : null,
            'basis_of_booking' => $detail->basis_of_booking,
            'freight' => $detail->freight,
            'home_delivery_charge' => $detail->home_delivery_charge,
            'disposition' => $detail->disposition,
            'status' => $detail->status,
        ];
    }

    /**
     * @param  array{
     *     consignor: ?string,
     *     from_station: ?string,
     *     to_station: ?string,
     *     goods_name: ?string,
     *     private_marka: ?string,
     *     packages: float|int|null,
     *     weight: float|int|null,
     *     gr_date: ?string,
     *     received_date: ?string,
     *     basis_of_booking: ?string,
     *     freight: float|int|null,
     *     home_delivery_charge: float|int|null,
     *     disposition: ?string,
     *     status: ?string
     * }  $source
     * @return array{
     *     consignor: ?string,
     *     from_station: ?string,
     *     goods_name: ?string,
     *     private_marka: ?string,
     *     packages: float|int|null,
     *     weight: float|int|null,
     *     gr_date: ?string,
     *     received_date: ?string,
     *     basis_of_booking: ?string
     * }
     */
    private function formatGrSourceForPrint(array $source): array
    {
        return [
            'consignor' => $source['consignor'],
            'from_station' => $source['from_station'],
            'goods_name' => $source['goods_name'],
            'private_marka' => $source['private_marka'],
            'packages' => $source['packages'],
            'weight' => $source['weight'],
            'gr_date' => $source['gr_date'] ? date('d/m/Y', strtotime($source['gr_date'])) : null,
            'received_date' => $source['received_date']
                ? date('d/m/Y', strtotime($source['received_date']))
                : null,
            'basis_of_booking' => $source['basis_of_booking'],
        ];
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
            'bank_account_id' => [
                'nullable',
                Rule::requiredIf(fn () => request('mode_of_payment') === 'Bank'),
                $this->existsInCompany('bank_accounts')->whereNull('deleted_at'),
            ],
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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePayment(array $validated): array
    {
        $mode = $validated['mode_of_payment'] ?? null;

        if ($mode !== 'Due') {
            $validated['payment_due_from_id'] = null;
        }

        if ($mode !== 'Bank') {
            $validated['bank_account_id'] = null;
        }

        $totals = GstCalculator::forDeliveryReceipt($validated, $this->branchGstRate(), $this->gstExemptionLimit());
        $validated['total_amount'] = $totals['total_amount'];
        $validated['gst'] = $totals['gst'];
        $validated['final_amount'] = $totals['final_amount'];

        return $validated;
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

    private function gstExemptionLimit(): float
    {
        $branch = $this->activeBranch();

        if (! $branch || ! $branch->has_gst) {
            return 0.0;
        }

        return (float) ($branch->gst_exemption_limit ?? 0);
    }

    protected function activeBranch(): ?Branch
    {
        return $this->tenant()->activeBranch();
    }
}
