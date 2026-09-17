<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AccountOptionsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function __invoke(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $accounts = Account::query()
            ->selectable()
            ->with('accountGroup:id,name,nature')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhereHas('accountGroup', fn ($g) => $g->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(fn (Account $account) => $account->toSelectOption())
            ->values();

        return response()->json(['accounts' => $accounts]);
    }
}
