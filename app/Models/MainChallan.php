<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainChallan extends Model
{
    use \App\Traits\BelongsToBranch;
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function fromStation()
    {
        return $this->belongsTo(Station::class, 'from_station_id');
    }

    public function toStation()
    {
        return $this->belongsTo(Station::class, 'to_station_id');
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function details()
    {
        return $this->hasMany(MainChallanDetail::class);
    }
}
