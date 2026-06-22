<?php

namespace App\Http\Controllers;

use App\Http\Requests\BiltyRequest;
use App\Models\Bilty;
use App\Models\Branch;
use App\Models\Party;
use App\Models\Vendor;
use App\Models\Station;
use App\Models\Good;
use App\Models\PackageType;
use App\Support\BiltyPrint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class BiltyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view bilties', only: ['index', 'show']),
            new Middleware('permission:create bilties', only: ['create', 'store']),
            new Middleware('permission:edit bilties', only: ['edit', 'update']),
            new Middleware('permission:delete bilties', only: ['destroy']),
        ];
    }

    public function index()
    {
        $bilties = Bilty::with(['company', 'consignor', 'consignee', 'fromStation', 'toStation'])->latest()->paginate(10);

        return Inertia::render('Bilties/Index', [
            'bilties' => $bilties,
        ]);
    }

    public function create()
    {
        $branch = $this->activeBranch();
        $nextNumber = \App\Support\DocumentNumber::next(
            Bilty::query(),
            'bilty_number',
            $branch?->bilty_initial
        );

        return Inertia::render('Bilties/Create', [
            'parties' => Party::all(),
            'vendors' => Vendor::all(),
            'stations' => Station::all(),
            'goods' => Good::all(),
            'package_types' => PackageType::all(),
            'next_bilty_number' => $nextNumber,
            'default_gst_percent' => $this->defaultGstPercent($branch),
        ]);
    }

    public function store(BiltyRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? 'Pending';
        $validated['created_by'] = auth()->id();

        DB::transaction(function () use ($validated) {
            $details = $validated['details'] ?? [];
            $ewayBills = $validated['eway_bills'] ?? [];
            unset($validated['details'], $validated['eway_bills'], $validated['eway_bill_no'], $validated['eway_bill_expiry_date']);

            $bilty = Bilty::create($validated);

            foreach ($details as $item) {
                if (! empty($item['good_id'])) {
                    $bilty->biltyDetails()->create([
                        'good_id' => $item['good_id'],
                        'package_type_id' => $item['package_type_id'],
                        'no_of_packages' => $item['no_of_packages'],
                        'gross_weight' => $item['gross_weight'],
                        'charged_weight' => $item['charged_weight'] ?? null,
                        'rate' => $item['rate'],
                        'rate_type' => $item['rate_type'] ?? 'Fixed',
                    ]);
                }
            }

            $this->syncEwayBills($bilty, $ewayBills);
        });

        return redirect()->route('bilties.index')->with('success', 'Bilty created successfully.');
    }

    public function edit(Bilty $bilty)
    {
        $bilty->load(['biltyDetails', 'ewayBills']);
        $branch = $this->activeBranch();

        return Inertia::render('Bilties/Edit', [
            'bilty' => $bilty,
            'parties' => Party::all(),
            'vendors' => Vendor::all(),
            'stations' => Station::all(),
            'goods' => Good::all(),
            'package_types' => PackageType::all(),
            'default_gst_percent' => $this->defaultGstPercent($branch),
        ]);
    }

    public function update(BiltyRequest $request, Bilty $bilty)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $bilty) {
            $details = $validated['details'] ?? [];
            $ewayBills = $validated['eway_bills'] ?? [];
            unset($validated['details'], $validated['eway_bills'], $validated['eway_bill_no'], $validated['eway_bill_expiry_date']);

            $bilty->update($validated);
            $bilty->biltyDetails()->delete();

            foreach ($details as $item) {
                if (! empty($item['good_id'])) {
                    $bilty->biltyDetails()->create([
                        'good_id' => $item['good_id'],
                        'package_type_id' => $item['package_type_id'],
                        'no_of_packages' => $item['no_of_packages'],
                        'gross_weight' => $item['gross_weight'],
                        'charged_weight' => $item['charged_weight'] ?? null,
                        'rate' => $item['rate'],
                        'rate_type' => $item['rate_type'] ?? 'Fixed',
                    ]);
                }
            }

            $this->syncEwayBills($bilty, $ewayBills);
        });

        return redirect()->route('bilties.index')->with('success', 'Bilty updated successfully.');
    }

    public function destroy(Bilty $bilty)
    {
        $bilty->delete();

        return redirect()->route('bilties.index')->with('success', 'Bilty deleted successfully.');
    }

    public function print(Request $request, Bilty $bilty)
    {
        $bilty->load(['fromStation', 'toStation', 'consignor', 'consignee', 'vendor', 'creator', 'biltyDetails.good', 'biltyDetails.packageType', 'ewayBills', 'branch.company', 'branch.city', 'branch.state']);
        $company = \App\Models\Company::find($bilty->company_id);
        $branch = $bilty->branch ?? new Branch;
        $printData = $this->buildPrintData($bilty);

        $printShowAmount = null;
        if ($request->has('show_amount')) {
            $printShowAmount = $request->boolean('show_amount');
        }

        $copiesToPrint = BiltyPrint::copiesToPrint($branch, $bilty, $printShowAmount);
        $copyPages = array_chunk($copiesToPrint, 2);
        $biltyNote = $branch->resolvedBiltyPrintConfig()['note'] ?? '';

        return view('prints.bilty', compact('bilty', 'company', 'branch', 'printData', 'copyPages', 'biltyNote'));
    }

    private function buildPrintData(Bilty $bilty): array
    {
        $detailRows = $bilty->biltyDetails->map(function ($detail) {
            return [
                'good' => $detail->good?->name ?? '—',
                'package_type' => $detail->packageType?->name ?? '—',
                'no_of_packages' => $detail->no_of_packages,
                'gross_weight' => $detail->gross_weight,
                'charged_weight' => $detail->charged_weight,
                'rate_type' => $detail->rate_type ?? 'Fixed',
                'rate' => $detail->rate,
                'amount' => $this->detailRowAmount($detail),
            ];
        });

        $addonTotal = collect($bilty->addon_charges ?? [])->sum(fn ($charge) => (float) ($charge['amount'] ?? 0));

        $subtotal =
            (float) ($bilty->freight ?? 0) +
            (float) ($bilty->bilty_charge ?? 0) +
            (float) ($bilty->surcharge ?? 0) +
            (float) ($bilty->handling_charge ?? 0) +
            (float) ($bilty->risk_dd_charge ?? 0) +
            (float) ($bilty->cartage ?? 0) +
            (float) ($bilty->other_charge ?? 0) +
            $addonTotal -
            (float) ($bilty->less_advance_received ?? 0);

        $gstRate = $bilty->gst_liability_by === 'Exempted' ? 0 : (float) ($bilty->gst ?? 0);
        $gstAmount = $subtotal * ($gstRate / 100);
        $totalAmount = (float) ($bilty->amount ?? ($subtotal + $gstAmount));

        $charges = [];

        $addCharge = function (string $label, $value) use (&$charges) {
            if ((float) $value != 0) {
                $charges[] = [
                    'label' => $label,
                    'amount' => (float) $value,
                ];
            }
        };

        $addCharge('Total Freight', $bilty->freight);
        $addCharge('Bilty Charge', $bilty->bilty_charge);
        $addCharge('Surcharge', $bilty->surcharge);
        $addCharge('Handling Charge', $bilty->handling_charge);
        $addCharge('Risk / DD Charge', $bilty->risk_dd_charge);
        $cartageLabel = 'Cartage';
        if ((float) ($bilty->cartage ?? 0) != 0 && ! empty($bilty->cartage_type)) {
            $cartageType = in_array(strtolower((string) $bilty->cartage_type), ['due'], true) ? 'Due' : 'Paid';
            $cartageLabel = 'Cartage ('.$cartageType.')';
        }
        $addCharge($cartageLabel, $bilty->cartage);
        $addCharge('Other Charge', $bilty->other_charge);

        foreach ($bilty->addon_charges ?? [] as $addon) {
            $amount = (float) ($addon['amount'] ?? 0);
            if ($amount == 0) {
                continue;
            }

            $label = $addon['particulars'] ?? $addon['description'] ?? 'Addon Charge';
            $charges[] = [
                'label' => $label,
                'amount' => $amount,
            ];
        }

        if ((float) ($bilty->less_advance_received ?? 0) != 0) {
            $charges[] = [
                'label' => 'Less Advance Received',
                'amount' => -(float) $bilty->less_advance_received,
            ];
        }

        if ($gstAmount != 0) {
            $charges[] = [
                'label' => 'GST ('.rtrim(rtrim(number_format($gstRate, 2), '0'), '.').'%)',
                'amount' => $gstAmount,
            ];
        }

        $cgst = (float) ($bilty->cgst ?? 0);
        $sgst = (float) ($bilty->sgst ?? 0);
        $igst = (float) ($bilty->igst ?? 0);

        if ($cgst == 0 && $sgst == 0 && $igst == 0 && $gstAmount != 0) {
            if ((float) ($bilty->igst ?? 0) > 0) {
                $igst = (float) $bilty->igst;
            } else {
                $cgst = round($gstAmount / 2, 2);
                $sgst = round($gstAmount - $cgst, 2);
            }
        }

        $lrChargeLines = [];

        $pushCharge = function (string $label, $value) use (&$lrChargeLines) {
            if ((float) $value != 0) {
                $lrChargeLines[] = ['label' => $label, 'amount' => (float) $value];
            }
        };

        $pushCharge('FREIGHT', $bilty->freight);
        $pushCharge('HANDLING', $bilty->handling_charge);
        $pushCharge('CARTAGE', $bilty->cartage);
        $pushCharge('STATIONERY CHAR.', $bilty->bilty_charge);
        $pushCharge('SURCHARGE', $bilty->surcharge);
        $pushCharge('RISK / DD CHAR.', $bilty->risk_dd_charge);
        $pushCharge('OTHER CHAR.', $bilty->other_charge);

        foreach ($bilty->addon_charges ?? [] as $addon) {
            $amount = (float) ($addon['amount'] ?? 0);
            if ($amount == 0) {
                continue;
            }

            $lrChargeLines[] = [
                'label' => strtoupper($addon['particulars'] ?? $addon['description'] ?? 'ADDON'),
                'amount' => $amount,
            ];
        }

        return [
            'detail_rows' => $detailRows,
            'detail_totals' => [
                'no_of_packages' => $detailRows->sum(fn ($row) => (float) ($row['no_of_packages'] ?? 0)),
                'gross_weight' => $detailRows->sum(fn ($row) => (float) ($row['gross_weight'] ?? 0)),
                'charged_weight' => $detailRows->sum(fn ($row) => (float) ($row['charged_weight'] ?? 0)),
                'amount' => $detailRows->sum(fn ($row) => (float) ($row['amount'] ?? 0)),
            ],
            'charges' => $charges,
            'lr_charge_lines' => $lrChargeLines,
            'charge_total' => round($subtotal, 2),
            'sgst' => round($sgst, 2),
            'cgst' => round($cgst, 2),
            'igst' => round($igst, 2),
            'less_advance' => (float) ($bilty->less_advance_received ?? 0),
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'gst_rate' => $gstRate,
            'total_amount' => $totalAmount,
        ];
    }

    private function detailRowAmount($detail): float
    {
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
    }

    private function syncEwayBills(Bilty $bilty, array $ewayBills): void
    {
        $keptIds = [];

        foreach ($ewayBills as $item) {
            if (empty($item['eway_bill_no'])) {
                continue;
            }

            $payload = [
                'eway_bill_no' => $item['eway_bill_no'],
                'eway_bill_date' => null,
                'eway_bill_valid_till' => $item['eway_bill_valid_till'] ?? null,
            ];

            if (! empty($item['id'])) {
                $existing = $bilty->ewayBills()->whereKey($item['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;
                    continue;
                }
            }

            $created = $bilty->ewayBills()->create($payload);
            $keptIds[] = $created->id;
        }

        if (empty($keptIds)) {
            $bilty->ewayBills()->delete();
        } else {
            $bilty->ewayBills()->whereNotIn('id', $keptIds)->delete();
        }
    }

    private function defaultGstPercent(?Branch $branch): float
    {
        if (! $branch || ! $branch->has_gst) {
            return 0;
        }

        return (float) ($branch->gst_slab ?? 0);
    }
}
