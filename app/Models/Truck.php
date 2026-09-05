<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Truck extends Model
{
    use \App\Traits\BelongsToCompany;
    use SoftDeletes;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function truckType()
    {
        return $this->belongsTo(TruckType::class, 'truck_type_id');
    }

    public function currentState()
    {
        return $this->belongsTo(State::class, 'current_state_id');
    }

    public function currentCity()
    {
        return $this->belongsTo(City::class, 'current_city_id');
    }

    public function currentStation()
    {
        return $this->belongsTo(Station::class, 'current_station_id');
    }

    public function truckOwner()
    {
        return $this->belongsTo(TruckOwner::class);
    }
}
