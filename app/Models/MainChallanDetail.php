<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainChallanDetail extends Model
{
    use \App\Traits\BelongsToCompany;
    use \App\Traits\BelongsToBranch;
    protected $guarded = [];

    public function mainChallan()
    {
        return $this->belongsTo(MainChallan::class);
    }

    public function challan()
    {
        return $this->belongsTo(Challan::class);
    }
}
