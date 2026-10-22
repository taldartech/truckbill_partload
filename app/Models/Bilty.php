<?php

namespace App\Models;

use App\Support\GstCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bilty extends Model
{
    use \App\Traits\BelongsToBranch;
    use \App\Traits\BelongsToCompany;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'addon_charges' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function consignor()
    {
        return $this->belongsTo(Party::class, 'consignor_id');
    }

    public function cartageAccount()
    {
        return $this->belongsTo(Account::class, 'cartage_account_id');
    }

    public function consignee()
    {
        return $this->belongsTo(Party::class, 'consignee_id');
    }

    public function fromStation()
    {
        return $this->belongsTo(Station::class, 'from_station_id');
    }

    public function toStation()
    {
        return $this->belongsTo(Station::class, 'to_station_id');
    }

    public function biltyDetails()
    {
        return $this->hasMany(BiltyDetail::class, 'bilty_id');
    }

    public function ewayBills()
    {
        return $this->hasMany(BiltyEwayBill::class, 'bilty_id');
    }

    public function challanDetails()
    {
        return $this->hasMany(ChallanDetail::class);
    }

    public function challans()
    {
        return $this->hasManyThrough(
            Challan::class,
            ChallanDetail::class,
            'bilty_id',
            'id',
            'id',
            'challan_id'
        );
    }

    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function invoices()
    {
        return $this->hasManyThrough(
            Invoice::class,
            InvoiceDetail::class,
            'bilty_id',
            'id',
            'id',
            'invoice_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function advanceBankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'advance_bank_account_id');
    }

    /**
     * The party billed for the freight: consignee for To Pay, consignor otherwise.
     */
    public function payerPartyId(): ?int
    {
        $partyId = $this->payment_type === 'To Pay' ? $this->consignee_id : $this->consignor_id;

        return $partyId ? (int) $partyId : null;
    }

    /**
     * Server-calculated charges and GST, using the branch exemption limit and
     * the branch/payer states for the CGST+SGST or IGST split.
     *
     * @return array{charges_total: float, gst_rate: float, gst_amount: float, cgst_amount: float, sgst_amount: float, igst_amount: float, gst_in_total: float, amount: float}
     */
    public function gstBreakdown(): array
    {
        $branch = $this->branch_id ? Branch::query()->withoutGlobalScopes()->find($this->branch_id) : null;
        $payerId = $this->payerPartyId();
        $payer = $payerId ? Party::query()->withoutGlobalScopes()->find($payerId) : null;

        return GstCalculator::forBilty(
            $this->attributesToArray(),
            $branch?->has_gst ? (float) ($branch->gst_exemption_limit ?? 0) : 0.0,
            $branch?->state_id ? (int) $branch->state_id : null,
            $payer?->state_id ? (int) $payer->state_id : null,
        );
    }
}
