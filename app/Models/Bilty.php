<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bilty extends Model
{
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

    public function vendor()
    {
        return $this->belongsTo(Party::class, 'vendor_id');
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
}