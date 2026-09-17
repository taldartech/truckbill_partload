<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Legacy entity ledgers are replaced by accounting reports.
 */
class LedgerReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view accounting_reports|view reports'),
        ];
    }

    public function partyLedger(Request $request)
    {
        return redirect()->route('accounting.reports.receivables', $request->query());
    }

    public function vendorLedger()
    {
        return redirect()->route('accounting.reports.payables');
    }

    public function agentLedger(Request $request)
    {
        return redirect()->route('accounting.reports.payables', $request->query());
    }

    public function cashBankBook(Request $request)
    {
        return redirect()->route('accounting.reports.cash-book', $request->query());
    }
}
