<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bilty extends Model
{
    use \App\Traits\BelongsToCompany;
    use \App\Traits\BelongsToBranch;

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

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
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
}