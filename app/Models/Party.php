<?php

namespace App\Models;

use App\Enums\ChargeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use \App\Traits\BelongsToCompany;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'consider_for_accounting' => 'boolean',
            'bilty_charge' => 'decimal:2',
            'bilty_charge_type' => ChargeType::class,
            'surcharge' => 'decimal:2',
            'surcharge_type' => ChargeType::class,
            'handling_charge' => 'decimal:2',
            'handling_charge_type' => ChargeType::class,
        ];
    }

    /**
     * Party default charge fields keyed by amount column => type column.
     *
     * @return array<string, string>
     */
    public static function chargeFields(): array
    {
        return [
            'bilty_charge' => 'bilty_charge_type',
            'surcharge' => 'surcharge_type',
            'handling_charge' => 'handling_charge_type',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
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
