<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use \App\Traits\BelongsToOwner;

    use SoftDeletes;

    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}