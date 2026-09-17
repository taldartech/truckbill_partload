<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Legacy Payment/Receipt vouchers are replaced by the accounting vouchers module.
 */
class VoucherController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view vouchers|view accounting_vouchers'),
        ];
    }

    public function index()
    {
        return redirect()->route('accounting.vouchers.index');
    }

    public function create()
    {
        return redirect()->route('accounting.vouchers.create', ['type' => 'receipt']);
    }

    public function store(Request $request)
    {
        return redirect()->route('accounting.vouchers.create');
    }

    public function show($voucher)
    {
        return redirect()->route('accounting.vouchers.index');
    }

    public function edit($voucher)
    {
        return redirect()->route('accounting.vouchers.index');
    }

    public function update(Request $request, $voucher)
    {
        return redirect()->route('accounting.vouchers.index');
    }

    public function destroy(Request $request, $voucher)
    {
        return redirect()->route('accounting.vouchers.index');
    }

    public function entityBalance()
    {
        return response()->json(['outstanding' => 0]);
    }
}
