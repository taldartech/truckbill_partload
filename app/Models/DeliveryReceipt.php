<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryReceipt extends Model
{
    use \App\Traits\BelongsToBranch;
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function consignee()
    {
        return $this->belongsTo(Party::class, 'consignee_id');
    }

    public function paymentDueFrom()
    {
        return $this->belongsTo(Party::class, 'payment_due_from_id');
    }
}
