<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Party;
use App\Models\Station;
use App\Models\Voucher;
use App\Models\Invoice;
use Carbon\Carbon;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view reports', only: ['biltyRegister', 'challanRegister', 'partyLedger']),
        ];
    }

    public function biltyRegister(Request $request)
    {
        $query = Bilty::with(['fromStation', 'toStation', 'consignor', 'consignee', 'truck']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            // Default to current month if no dates provided
            $query->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
        }

        if ($request->filled('station_id')) {
            $query->where(function($q) use ($request) {
                $q->where('from_station_id', $request->station_id)
                  ->orWhere('to_station_id', $request->station_id);
            });
        }

        if ($request->filled('party_id')) {
            $query->where(function($q) use ($request) {
                $q->where('consignor_id', $request->party_id)
                  ->orWhere('consignee_id', $request->party_id);
            });
        }

        $bilties = $query->orderBy('date', 'desc')->get();

        return Inertia::render('Reports/BiltyRegister', [
            'bilties' => $bilties,
            'stations' => Station::all(),
            'parties' => Party::all(),
            'filters' => $request->all(['start_date', 'end_date', 'station_id', 'party_id'])
        ]);
    }

    public function challanRegister(Request $request)
    {
        $query = Challan::with(['fromStation', 'toStation', 'truck', 'driver']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            $query->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
        }

        $challans = $query->orderBy('date', 'desc')->get();

        return Inertia::render('Reports/ChallanRegister', [
            'challans' => $challans,
            'filters' => $request->all(['start_date', 'end_date'])
        ]);
    }

    public function partyLedger(Request $request)
    {
        $partyId = $request->input('party_id');
        $ledger = [];
        $runningBalance = 0;
        $party = null;

        if ($partyId) {
            $party = Party::find($partyId);
            
            // Get Invoices (Debit)
            $invoices = Invoice::where('party_id', $partyId)
                ->when($request->filled('start_date'), function($q) use ($request) {
                    return $q->whereDate('date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function($q) use ($request) {
                    return $q->whereDate('date', '<=', $request->end_date);
                })
                ->get()
                ->map(function($invoice) {
                    return [
                        'date' => $invoice->date,
                        'type' => 'Invoice',
                        'ref_no' => $invoice->invoice_no,
                        'description' => 'Tax Invoice ' . $invoice->invoice_no,
                        'debit' => (float)$invoice->net_amount,
                        'credit' => 0,
                    ];
                });

            // Get Vouchers (Credit - Receipt)
            $vouchers = Voucher::where('party_id', $partyId)
                ->whereIn('voucher_type', ['Receipt', 'Journal'])
                ->when($request->filled('start_date'), function($q) use ($request) {
                    return $q->whereDate('date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function($q) use ($request) {
                    return $q->whereDate('date', '<=', $request->end_date);
                })
                ->get()
                ->map(function($voucher) {
                    return [
                        'date' => $voucher->date,
                        'type' => 'Voucher',
                        'ref_no' => $voucher->voucher_no,
                        'description' => $voucher->remark ?? $voucher->voucher_type . ' Voucher',
                        'debit' => 0,
                        'credit' => (float)$voucher->amount,
                    ];
                });

            // Combine and sort by date
            $ledger = collect($invoices)->concat($vouchers)->sortBy('date')->values()->all();

            // Calculate running balance (Opening balance not calculated for simplicity in this demo)
            foreach ($ledger as &$entry) {
                $runningBalance += ($entry['debit'] - $entry['credit']);
                $entry['balance'] = $runningBalance;
            }
        }

        return Inertia::render('Reports/PartyLedger', [
            'parties' => Party::all(),
            'party' => $party,
            'ledger' => $ledger,
            'filters' => $request->all(['party_id', 'start_date', 'end_date'])
        ]);
    }
}
