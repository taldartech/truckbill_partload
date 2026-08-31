<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Party;
use App\Services\Accounting\AccountingPoster;
use App\Support\DocumentNumber;
use App\Support\RedirectsWithOptionalPrint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InvoiceController extends Controller implements HasMiddleware
{
    use RedirectsWithOptionalPrint;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view invoices', only: ['index', 'show', 'print']),
            new Middleware('permission:create invoices|edit invoices', only: ['pendingBilties']),
            new Middleware('permission:create invoices', only: ['create', 'store']),
            new Middleware('permission:edit invoices', only: ['edit', 'update']),
            new Middleware('permission:delete invoices', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->endOfMonth()->toDateString();
        $search = trim((string) ($validated['search'] ?? ''));

        $latestInvoiceId = Invoice::query()->max('id');

        $invoices = Invoice::query()
            ->with('party')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('invoice_no', 'like', '%'.$search.'%');
            })
            ->when($fromDate, fn ($query) => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('date', '<=', $toDate))
            ->when(! empty($validated['party_id']), function ($query) use ($validated) {
                $query->where('party_id', $validated['party_id']);
            })
            ->when(! empty($validated['status']), function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $invoices->getCollection()->transform(function (Invoice $invoice) use ($latestInvoiceId) {
            $invoice->can_delete = (int) $invoice->id === (int) $latestInvoiceId
                && $invoice->status === 'Pending';
            $invoice->can_edit = true;

            return $invoice;
        });

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'parties' => Party::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'party_id' => isset($validated['party_id']) ? (string) $validated['party_id'] : '',
                'status' => $validated['status'] ?? '',
            ],
        ]);
    }

    public function create()
    {
        $branch = $this->activeBranch();

        return Inertia::render('Invoices/Create', [
            'parties' => Party::all(),
            'next_invoice_no' => DocumentNumber::next(
                Invoice::query(),
                'invoice_no',
                $branch?->invoice_initial
            ),
        ]);
    }

    public function pendingBilties(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        $currentBiltyIds = [];
        if (! empty($validated['invoice_id'])) {
            $currentBiltyIds = InvoiceDetail::where('invoice_id', $validated['invoice_id'])->pluck('bilty_id')->all();
        }

        $bilties = Bilty::query()
            ->where('payment_type', 'TBB')
            ->where('consignor_id', $validated['party_id'])
            ->where(function ($query) use ($currentBiltyIds) {
                $query->where(function ($inner) {
                    $inner->whereNull('invoice_number')->orWhere('invoice_number', '');
                });

                if (! empty($currentBiltyIds)) {
                    $query->orWhereIn('id', $currentBiltyIds);
                }
            })
            ->with([
                'fromStation:id,name',
                'toStation:id,name',
                'biltyDetails' => fn ($query) => $query->with('good:id,name'),
            ])
            ->orderBy('date')
            ->get([
                'id', 'bilty_number', 'date', 'freight', 'amount',
                'from_station_id', 'to_station_id',
                'bilty_charge', 'surcharge', 'handling_charge', 'risk_dd_charge',
                'cartage', 'addon_charges', 'less_advance_received', 'gst', 'gst_liability_by',
            ]);

        return response()->json(['bilties' => $bilties]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => 'required|string|unique:invoices',
            'date' => 'required|date',
            'party_id' => 'required|exists:parties,id',
            'remark' => 'nullable|string',
            'bilty_ids' => 'required|array|min:1',
            'bilty_ids.*' => 'exists:bilties,id',
            'bilty_detail_freight' => 'nullable|array',
            'bilty_detail_freight.*' => 'array|min:1',
            'bilty_detail_freight.*.*.id' => 'required|integer|exists:bilty_details,id',
            'bilty_detail_freight.*.*.rate_type' => 'required|string|in:Fixed,Per Package,Per KG,Per Actual Weight,Per Charged Weight',
            'bilty_detail_freight.*.*.rate' => 'required|numeric|min:0.01',
        ]);

        $biltyDetailFreight = $validated['bilty_detail_freight'] ?? [];
        $partyId = (int) $validated['party_id'];
        $invoiceNo = $validated['invoice_no'];

        $bilties = Bilty::with('biltyDetails')->whereIn('id', $validated['bilty_ids'])->get();

        if ($bilties->count() !== count($validated['bilty_ids'])) {
            throw ValidationException::withMessages([
                'bilty_ids' => 'One or more selected bilties are invalid.',
            ]);
        }

        $invoicedBilty = $bilties->first(fn (Bilty $bilty) => filled($bilty->invoice_number));

        if ($invoicedBilty) {
            throw ValidationException::withMessages([
                'bilty_ids' => "Bilty {$invoicedBilty->bilty_number} is already invoiced.",
            ]);
        }

        $preparedBilties = [];

        foreach ($bilties as $bilty) {
            if ($bilty->payment_type !== 'TBB') {
                throw ValidationException::withMessages([
                    'bilty_ids' => 'Only TBB bilties can be added to an invoice.',
                ]);
            }

            if ((int) $bilty->consignor_id !== $partyId) {
                throw ValidationException::withMessages([
                    'bilty_ids' => 'Selected bilties must belong to the chosen party (consignor).',
                ]);
            }

            $detailRows = $biltyDetailFreight[(string) $bilty->id] ?? $biltyDetailFreight[$bilty->id] ?? null;

            if ($detailRows) {
                foreach ($detailRows as $row) {
                    $detail = $bilty->biltyDetails->firstWhere('id', (int) $row['id']);

                    if (! $detail || (int) $detail->bilty_id !== (int) $bilty->id) {
                        throw ValidationException::withMessages([
                            'bilty_detail_freight' => "Invalid detail row for bilty {$bilty->bilty_number}.",
                        ]);
                    }

                    $detail->update([
                        'rate_type' => $row['rate_type'],
                        'rate' => $row['rate'],
                    ]);
                }

                $bilty->load('biltyDetails');
            }

            $freight = round($bilty->biltyDetails->sum(function (BiltyDetail $detail) {
                $rate = (float) ($detail->rate ?? 0);
                $weight = (float) ($detail->gross_weight ?? 0);
                $chargedWeight = (float) ($detail->charged_weight ?? 0);
                $packages = (float) ($detail->no_of_packages ?? 0);
                $rateType = $detail->rate_type ?? 'Fixed';

                if (in_array($rateType, ['Per Actual Weight', 'Per KG'], true)) {
                    return $rate * $weight;
                }

                if ($rateType === 'Per Charged Weight') {
                    return $rate * $chargedWeight;
                }

                if ($rateType === 'Per Package') {
                    return $rate * $packages;
                }

                return $rate;
            }), 2);

            if ($freight <= 0) {
                throw ValidationException::withMessages([
                    'bilty_detail_freight' => "Freight is required for bilty {$bilty->bilty_number}.",
                ]);
            }

            $addonTotal = collect($bilty->addon_charges ?? [])->sum(function ($charge) {
                return (float) ($charge['amount'] ?? 0);
            });

            $subtotal = $freight
                + (float) ($bilty->bilty_charge ?? 0)
                + (float) ($bilty->surcharge ?? 0)
                + (float) ($bilty->handling_charge ?? 0)
                + (float) ($bilty->risk_dd_charge ?? 0)
                + (float) ($bilty->cartage ?? 0)
                + $addonTotal
                - (float) ($bilty->less_advance_received ?? 0);

            $gstRate = $bilty->gst_liability_by === 'Exempted' ? 0 : (float) ($bilty->gst ?? 0);
            $gstAmount = round($subtotal * $gstRate / 100, 2);
            $gstInTotal = $bilty->gst_liability_by === 'Transporter' ? $gstAmount : 0.0;
            $amount = round($subtotal + $gstInTotal, 2);

            $preparedBilties[] = [
                'bilty' => $bilty,
                'freight' => $freight,
                'subtotal' => round($subtotal, 2),
                'gst_amount' => $gstAmount,
                'amount' => $amount,
            ];
        }

        $totalAmount = round(collect($preparedBilties)->sum('subtotal'), 2);
        $gstAmount = round(collect($preparedBilties)->sum('gst_amount'), 2);
        $netAmount = round(collect($preparedBilties)->sum('amount'), 2);

        $invoice = DB::transaction(function () use ($validated, $preparedBilties, $invoiceNo, $totalAmount, $gstAmount, $netAmount) {
            $invoice = Invoice::create([
                'invoice_no' => $invoiceNo,
                'date' => $validated['date'],
                'party_id' => $validated['party_id'],
                'status' => 'Pending',
                'remark' => $validated['remark'],
                'total_amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'net_amount' => $netAmount,
            ]);

            foreach ($preparedBilties as $item) {
                $bilty = $item['bilty'];

                $bilty->update([
                    'freight' => $item['freight'],
                    'amount' => $item['amount'],
                    'invoice_number' => $invoiceNo,
                ]);

                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'bilty_id' => $bilty->id,
                    'amount' => $item['amount'],
                ]);
            }

            app(AccountingPoster::class)->syncInvoice($invoice);

            return $invoice;
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'invoices.index',
            'invoices.print',
            $invoice,
            'Invoice created successfully.',
        );
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'party',
            'details.bilty.fromStation',
            'details.bilty.toStation',
        ]);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load([
            'party',
            'details.bilty.fromStation',
            'details.bilty.toStation',
            'details.bilty.biltyDetails.good',
        ]);

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'parties' => Party::all(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'party_id' => 'required|exists:parties,id',
            'remark' => 'nullable|string',
            'bilty_ids' => 'required|array|min:1',
            'bilty_ids.*' => 'exists:bilties,id',
            'bilty_detail_freight' => 'nullable|array',
            'bilty_detail_freight.*' => 'array|min:1',
            'bilty_detail_freight.*.*.id' => 'required|integer|exists:bilty_details,id',
            'bilty_detail_freight.*.*.rate_type' => 'required|string|in:Fixed,Per Package,Per KG,Per Actual Weight,Per Charged Weight',
            'bilty_detail_freight.*.*.rate' => 'required|numeric|min:0.01',
        ]);

        $biltyDetailFreight = $validated['bilty_detail_freight'] ?? [];
        $partyId = (int) $validated['party_id'];
        $invoiceNo = $invoice->invoice_no;

        $bilties = Bilty::with('biltyDetails')->whereIn('id', $validated['bilty_ids'])->get();

        if ($bilties->count() !== count($validated['bilty_ids'])) {
            throw ValidationException::withMessages([
                'bilty_ids' => 'One or more selected bilties are invalid.',
            ]);
        }

        $invoicedBilty = $bilties->first(function (Bilty $bilty) use ($invoice) {
            return filled($bilty->invoice_number) && $bilty->invoice_number !== $invoice->invoice_no;
        });

        if ($invoicedBilty) {
            throw ValidationException::withMessages([
                'bilty_ids' => "Bilty {$invoicedBilty->bilty_number} is already invoiced on another invoice.",
            ]);
        }

        $preparedBilties = [];

        foreach ($bilties as $bilty) {
            if ($bilty->payment_type !== 'TBB') {
                throw ValidationException::withMessages([
                    'bilty_ids' => 'Only TBB bilties can be added to an invoice.',
                ]);
            }

            if ((int) $bilty->consignor_id !== $partyId) {
                throw ValidationException::withMessages([
                    'bilty_ids' => 'Selected bilties must belong to the chosen party (consignor).',
                ]);
            }

            $detailRows = $biltyDetailFreight[(string) $bilty->id] ?? $biltyDetailFreight[$bilty->id] ?? null;

            if ($detailRows) {
                foreach ($detailRows as $row) {
                    $detail = $bilty->biltyDetails->firstWhere('id', (int) $row['id']);

                    if (! $detail || (int) $detail->bilty_id !== (int) $bilty->id) {
                        throw ValidationException::withMessages([
                            'bilty_detail_freight' => "Invalid detail row for bilty {$bilty->bilty_number}.",
                        ]);
                    }

                    $detail->update([
                        'rate_type' => $row['rate_type'],
                        'rate' => $row['rate'],
                    ]);
                }

                $bilty->load('biltyDetails');
            }

            $freight = round($bilty->biltyDetails->sum(function (BiltyDetail $detail) {
                $rate = (float) ($detail->rate ?? 0);
                $weight = (float) ($detail->gross_weight ?? 0);
                $chargedWeight = (float) ($detail->charged_weight ?? 0);
                $packages = (float) ($detail->no_of_packages ?? 0);
                $rateType = $detail->rate_type ?? 'Fixed';

                if (in_array($rateType, ['Per Actual Weight', 'Per KG'], true)) {
                    return $rate * $weight;
                }

                if ($rateType === 'Per Charged Weight') {
                    return $rate * $chargedWeight;
                }

                if ($rateType === 'Per Package') {
                    return $rate * $packages;
                }

                return $rate;
            }), 2);

            if ($freight <= 0) {
                throw ValidationException::withMessages([
                    'bilty_detail_freight' => "Freight is required for bilty {$bilty->bilty_number}.",
                ]);
            }

            $addonTotal = collect($bilty->addon_charges ?? [])->sum(function ($charge) {
                return (float) ($charge['amount'] ?? 0);
            });

            $subtotal = $freight
                + (float) ($bilty->bilty_charge ?? 0)
                + (float) ($bilty->surcharge ?? 0)
                + (float) ($bilty->handling_charge ?? 0)
                + (float) ($bilty->risk_dd_charge ?? 0)
                + (float) ($bilty->cartage ?? 0)
                + $addonTotal
                - (float) ($bilty->less_advance_received ?? 0);

            $gstRate = $bilty->gst_liability_by === 'Exempted' ? 0 : (float) ($bilty->gst ?? 0);
            $gstAmount = round($subtotal * $gstRate / 100, 2);
            $gstInTotal = $bilty->gst_liability_by === 'Transporter' ? $gstAmount : 0.0;
            $amount = round($subtotal + $gstInTotal, 2);

            $preparedBilties[] = [
                'bilty' => $bilty,
                'freight' => $freight,
                'subtotal' => round($subtotal, 2),
                'gst_amount' => $gstAmount,
                'amount' => $amount,
            ];
        }

        $totalAmount = round(collect($preparedBilties)->sum('subtotal'), 2);
        $gstAmount = round(collect($preparedBilties)->sum('gst_amount'), 2);
        $netAmount = round(collect($preparedBilties)->sum('amount'), 2);

        $invoice->load('details');
        $removedBiltyIds = $invoice->details
            ->pluck('bilty_id')
            ->diff(collect($validated['bilty_ids']))
            ->values();

        DB::transaction(function () use ($invoice, $validated, $preparedBilties, $invoiceNo, $totalAmount, $gstAmount, $netAmount, $removedBiltyIds) {
            if ($removedBiltyIds->isNotEmpty()) {
                Bilty::whereIn('id', $removedBiltyIds)->update(['invoice_number' => null]);
            }

            $invoice->details()->delete();

            foreach ($preparedBilties as $item) {
                $bilty = $item['bilty'];

                $bilty->update([
                    'freight' => $item['freight'],
                    'amount' => $item['amount'],
                    'invoice_number' => $invoiceNo,
                ]);

                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'bilty_id' => $bilty->id,
                    'amount' => $item['amount'],
                ]);
            }

            $invoice->update([
                'date' => $validated['date'],
                'party_id' => $validated['party_id'],
                'remark' => $validated['remark'],
                'total_amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'net_amount' => $netAmount,
            ]);

            app(AccountingPoster::class)->syncInvoice($invoice->fresh());
        });

        return $this->indexRedirectWithOptionalPrint(
            $request,
            'invoices.index',
            'invoices.print',
            $invoice,
            'Invoice updated successfully.',
        );
    }

    public function destroy(
        Request $request,
        Invoice $invoice,
        AccountingPoster $poster
    ) {
        $latestInvoiceId = Invoice::query()->max('id');

        if ((int) $invoice->id !== (int) $latestInvoiceId) {
            throw ValidationException::withMessages([
                'invoice' => 'Only the latest invoice can be deleted.',
            ]);
        }

        if ($invoice->status !== 'Pending') {
            throw ValidationException::withMessages([
                'invoice' => 'Only pending invoices can be deleted.',
            ]);
        }

        $invoice->load('details');

        $biltyIds = $invoice->details->pluck('bilty_id');

        DB::transaction(function () use ($request, $invoice, $biltyIds, $poster) {
            if ($biltyIds->isNotEmpty()) {
                Bilty::whereIn('id', $biltyIds)->update(['invoice_number' => null]);
            }

            $poster->clearReference($invoice);

            $invoice->update([
                'delete_remark' => $request->delete_remark,
                'deleted_by' => Auth::id(),
            ]);

            $invoice->delete();
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['party', 'details.bilty.fromStation', 'details.bilty.toStation', 'branch']);
        $company = Company::find($invoice->company_id);
        $branch = $invoice->branch;

        return view('prints.invoice', compact('invoice', 'company', 'branch'));
    }
}
