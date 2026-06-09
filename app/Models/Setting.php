<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use \App\Traits\BelongsToCompany;
    use \App\Traits\BelongsToBranch;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
