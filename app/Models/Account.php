<?php

namespace App\Models;

use App\Enums\AccountNature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Account extends Model
{
    use \App\Traits\BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'is_selectable' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function voucherEntries(): HasMany
    {
        return $this->hasMany(AccountingVoucherEntry::class);
    }

    public function nature(): ?AccountNature
    {
        return $this->accountGroup?->nature;
    }

    public function hasPostedHistory(): bool
    {
        return $this->voucherEntries()->exists();
    }

    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_selectable', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array{id:int,name:string,code:?string,group_name:?string,nature:?string,reference_type:?string}
     */
    public function toSelectOption(): array
    {
        $this->loadMissing('accountGroup');

        $referenceType = $this->reference_type
            ? class_basename($this->reference_type)
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'group_name' => $this->accountGroup?->name,
            'nature' => $this->accountGroup?->nature?->value,
            'reference_type' => $referenceType,
            'label' => trim(implode(' · ', array_filter([
                $this->name,
                $this->accountGroup?->name,
                $referenceType,
            ]))),
        ];
    }
}
