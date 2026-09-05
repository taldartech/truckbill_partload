<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InwardRegister extends Model
{
    use BelongsToBranch;
    use BelongsToCompany;
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

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function details()
    {
        return $this->hasMany(InwardRegisterDetail::class);
    }
}
