<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use \App\Traits\BelongsToBranch;
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }
}
