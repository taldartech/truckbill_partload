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

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function truckType()
    {
        return $this->belongsTo(TruckType::class, 'truck_type_id');
    }
}