<?php

namespace App\Http\Controllers;

use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Party;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view reports', only: ['biltyRegister', 'challanRegister']),
        ];
    }

    public function biltyRegister(Request $request)
    {
        $query = Bilty::with(['fromStation', 'toStation', 'consignor', 'consignee']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            // Default to current month if no dates provided
            $query->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year);
        }

        if ($request->filled('station_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_station_id', $request->station_id)
                    ->orWhere('to_station_id', $request->station_id);
            });
        }

        if ($request->filled('party_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('consignor_id', $request->party_id)
                    ->orWhere('consignee_id', $request->party_id);
            });
        }

        $bilties = $query->orderBy('date', 'desc')->get();

        return Inertia::render('Reports/BiltyRegister', [
            'bilties' => $bilties,
            'stations' => Station::all(),
            'parties' => Party::all(),
            'filters' => $request->all(['start_date', 'end_date', 'station_id', 'party_id']),
        ]);
    }

    public function challanRegister(Request $request)
    {
        $query = Challan::with(['fromStation', 'toStation', 'truck']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            $query->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year);
        }

        $challans = $query->orderBy('date', 'desc')->get();

        return Inertia::render('Reports/ChallanRegister', [
            'challans' => $challans,
            'filters' => $request->all(['start_date', 'end_date']),
        ]);
    }
}
