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
}
