<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use \App\Traits\BelongsToCompany;
    use \App\Traits\BelongsToBranch;
    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bilty()
    {
        return $this->belongsTo(Bilty::class);
    }
}
