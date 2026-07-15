<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InwardRegisterDetail extends Model
{
    use \App\Traits\BelongsToBranch;
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    public function inwardRegister()
    {
        return $this->belongsTo(InwardRegister::class);
    }

    public function fromStation()
    {
        return $this->belongsTo(Station::class, 'from_station_id');
    }

    public function toStation()
    {
        return $this->belongsTo(Station::class, 'to_station_id');
    }

    public function consignor()
    {
        return $this->belongsTo(Party::class, 'consignor_id');
    }

    public function consignee()
    {
        return $this->belongsTo(Party::class, 'consignee_id');
    }

    public function good()
    {
        return $this->belongsTo(Good::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function challanDetail()
    {
        return $this->hasOne(ChallanDetail::class);
    }
}
