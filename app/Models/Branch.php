<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'gst_exemption_limit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (Branch $branch) {
            $branch->settings()->create([
                'bilty_print_config' => BranchSetting::defaultBiltyPrintConfig(),
            ]);
        });
    }

    public function settings(): HasOne
    {
        return $this->hasOne(BranchSetting::class);
    }

    public function resolvedBiltyPrintConfig(): array
    {
        $this->loadMissing('settings');

        return $this->settings?->resolvedBiltyPrintConfig()
            ?? BranchSetting::defaultBiltyPrintConfig();
    }

    public function getBiltyInitialAttribute(): ?string
    {
        return $this->documentInitial('bilty_initial');
    }

    public function getChallanInitialAttribute(): ?string
    {
        return $this->documentInitial('challan_initial');
    }

    public function getMainChallanInitialAttribute(): ?string
    {
        return $this->documentInitial('main_challan_initial');
    }

    public function getInvoiceInitialAttribute(): ?string
    {
        return $this->documentInitial('invoice_initial');
    }

    public function getInwardRegisterInitialAttribute(): ?string
    {
        return $this->documentInitial('inward_register_initial');
    }

    private function documentInitial(string $column): ?string
    {
        $this->loadMissing('settings');

        return $this->settings?->{$column};
    }

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

    public function state()
    {
        return $this->belongsTo(State::class);
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
