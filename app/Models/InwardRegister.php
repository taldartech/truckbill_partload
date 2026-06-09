<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InwardRegister extends Model
{
    use \App\Traits\BelongsToCompany;
    use \App\Traits\BelongsToBranch;

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

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function details()
    {
        return $this->hasMany(InwardRegisterDetail::class);
    }
}
