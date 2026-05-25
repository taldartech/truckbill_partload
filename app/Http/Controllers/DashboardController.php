<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Truck;
use App\Models\Voucher;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        
        // Build the basic stats
        $stats = [
            'total_bilties' => Bilty::count(),
            'today_bilties' => Bilty::whereDate('date', $today)->count(),
            'total_freight' => Bilty::sum('amount') ?? 0,
            
            'total_challans' => Challan::count(),
            'today_challans' => Challan::whereDate('date', $today)->count(),
            
            'active_trucks' => Truck::count(),
            
            'total_vouchers' => Voucher::count(),
            
            // Getting 5 recent bilties
            'recent_bilties' => Bilty::with('consignee')->orderBy('id', 'desc')->take(5)->get(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats
        ]);
    }
}
