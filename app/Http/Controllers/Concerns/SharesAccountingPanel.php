<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Accounting\AccountingAuditTrail;
use Illuminate\Database\Eloquent\Model;

trait SharesAccountingPanel
{
    /**
     * Vouchers posted from the document, or null when the user cannot see accounting.
     *
     * @return list<array<string, mixed>>|null
     */
    protected function accountingPanel(Model $document): ?array
    {
        if (! request()->user()?->can('view accounting_vouchers')) {
            return null;
        }

        return app(AccountingAuditTrail::class)->panelFor($document);
    }
}
