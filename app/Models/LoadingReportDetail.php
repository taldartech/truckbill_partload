<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoadingReportDetail extends Model
{
    use \App\Traits\BelongsToBranch;
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    public function loadingReport()
    {
        return $this->belongsTo(LoadingReport::class);
    }

    public function bilty()
    {
        return $this->belongsTo(Bilty::class);
    }

    public function inwardRegisterDetail()
    {
        return $this->belongsTo(InwardRegisterDetail::class);
    }

    public function good()
    {
        return $this->belongsTo(Good::class);
    }

    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }

    public function consignee()
    {
        return $this->belongsTo(Party::class, 'consignee_id');
    }
}
