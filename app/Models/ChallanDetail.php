<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallanDetail extends Model
{
    use \App\Traits\BelongsToCompany;
    use \App\Traits\BelongsToBranch;
    protected $guarded = [];
    
    public function bilty()
    {
        return $this->belongsTo(Bilty::class);
    }

    public function inwardRegisterDetail()
    {
        return $this->belongsTo(InwardRegisterDetail::class);
    }

    public function toStation()
    {
        return $this->belongsTo(Station::class, 'to_station_id');
    }
}
