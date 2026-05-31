<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallanDetail extends Model
{
    protected $guarded = [];
    
    public function bilty()
    {
        return $this->belongsTo(Bilty::class);
    }
}
