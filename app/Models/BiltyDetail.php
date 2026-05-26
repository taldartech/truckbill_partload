<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiltyDetail extends Model
{
    protected $guarded = [];

    public function good()
    {
        return $this->belongsTo(Good::class);
    }

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }
}
