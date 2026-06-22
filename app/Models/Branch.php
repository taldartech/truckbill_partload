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
        'bilty_print_config' => 'array',
    ];

    public static function defaultBiltyPrintConfig(): array
    {
        return [
            'ask_amount_at_print' => false,
            'note' => '',
            'copies' => [
                'consignor' => ['enabled' => true, 'show_amount' => true],
                'consignee' => ['enabled' => true, 'show_amount' => false],
                'driver' => ['enabled' => true, 'show_amount' => false],
                'office' => ['enabled' => true, 'show_amount' => true],
            ],
        ];
    }

    public function resolvedBiltyPrintConfig(): array
    {
        $defaults = self::defaultBiltyPrintConfig();
        $stored = is_array($this->bilty_print_config) ? $this->bilty_print_config : [];

        $config = [
            'ask_amount_at_print' => (bool) ($stored['ask_amount_at_print'] ?? $defaults['ask_amount_at_print']),
            'note' => self::resolveBiltyPrintNote($stored),
            'copies' => [],
        ];

        foreach ($defaults['copies'] as $key => $defaultCopy) {
            $storedCopy = is_array($stored['copies'][$key] ?? null) ? $stored['copies'][$key] : [];

            $config['copies'][$key] = [
                'enabled' => (bool) ($storedCopy['enabled'] ?? $defaultCopy['enabled']),
                'show_amount' => (bool) ($storedCopy['show_amount'] ?? $defaultCopy['show_amount']),
            ];
        }

        return $config;
    }

    private static function resolveBiltyPrintNote(array $stored): string
    {
        $note = trim((string) ($stored['note'] ?? ''));

        if ($note !== '') {
            return $note;
        }

        foreach ($stored['copies'] ?? [] as $copy) {
            if (! is_array($copy)) {
                continue;
            }

            $legacyNote = trim((string) ($copy['note'] ?? ''));

            if ($legacyNote !== '') {
                return $legacyNote;
            }
        }

        return '';
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
