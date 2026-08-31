<?php

namespace App\Models;

use App\Traits\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Good extends Model
{
    use BelongsToOwner;
    use SoftDeletes;

    protected $guarded = [];
}
