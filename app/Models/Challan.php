<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Challan extends Model
{
    use \App\Traits\BelongsToCompany;
    use \App\Traits\BelongsToBranch;

    use SoftDeletes;

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
        return $this->hasMany(ChallanDetail::class);
    }

    public function payByAgent()
    {
        return $this->belongsTo(Agent::class, 'pay_by_agent_id');
    }

    public function deliveryAtAgent()
    {
        return $this->belongsTo(Agent::class, 'delivery_at_agent_id');
    }
}