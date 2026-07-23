<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
