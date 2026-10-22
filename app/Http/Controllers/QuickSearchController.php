<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\MainChallan;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuickSearchController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));

        $bilties = collect();
        $detail = null;

        if ($q !== '') {
            $bilties = Bilty::query()
                ->with(['consignor', 'consignee', 'fromStation', 'toStation', 'cartageAccount'])
                ->where('bilty_number', 'like', '%'.$q.'%')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(50)
                ->get();

            // Exact match → load all related info
            $exact = Bilty::query()
                ->where('bilty_number', $q)
                ->first();

            if ($exact) {
                $exact->load([
                    'consignor',
                    'consignee',
                    'fromStation',
                    'toStation',
                    'cartageAccount',
                    'biltyDetails.good',
                    'biltyDetails.packageType',
                    'ewayBills',
                    'challans.fromStation',
                    'challans.toStation',
                    'challans.truck',
                    'challans.mainChallans',
                    'invoices.party',
                ]);

                // Fetch main challans linked via challans on this bilty
                $challanIds = $exact->challans->pluck('id');
                $mainChallans = MainChallan::query()
                    ->whereHas('details', fn ($q) => $q->whereIn('challan_id', $challanIds))
                    ->with(['fromStation', 'toStation', 'truck', 'details.challan'])
                    ->get();

                // Fetch vouchers linked to the consignor / consignee parties
                $partyIds = collect([
                    $exact->consignor_id,
                    $exact->consignee_id,
                ])->filter()->unique()->values();

                $vouchers = Voucher::query()
                    ->where('accountable_type', \App\Models\Party::class)
                    ->whereIn('accountable_id', $partyIds)
                    ->orderByDesc('date')
                    ->get();

                $detail = [
                    'bilty' => $exact,
                    'challans' => $exact->challans,
                    'main_challans' => $mainChallans,
                    'mainChallans' => $mainChallans,
                    'invoices' => $exact->invoices,
                    'vouchers' => $vouchers,
                ];
            }
        }

        return Inertia::render('QuickSearch', [
            'bilties' => $bilties,
            'query' => $q,
            'detail' => $detail,
        ]);
    }
}
