<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use \App\Traits\BelongsToCompany;

    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'has_gst' => 'boolean',
        'use_company_logo' => 'boolean',
        'is_head_office' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function resolvedLogo(): ?string
    {
        if ($this->use_company_logo) {
            $company = $this->relationLoaded('company')
                ? $this->company
                : $this->company()->first(['id', 'company_logo']);

            return $company?->company_logo;
        }

        return $this->branch_logo;
    }
}
