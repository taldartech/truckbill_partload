<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Good extends Model
{
    use \App\Traits\BelongsToCompany;
    protected $guarded = [];
    //
}
