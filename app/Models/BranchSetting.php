<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSetting extends Model
{
    public const BOB_KEYS = ['to_pay', 'paid', 'tbb', 'foc'];

    public const COPY_KEYS = ['consignor', 'consignee', 'driver', 'office'];

    protected $guarded = [];

    protected $casts = [
        'bilty_print_config' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (BranchSetting $setting) {
            if (is_array($setting->bilty_print_config)) {
                $setting->bilty_print_config = self::normalizeBiltyPrintConfig($setting->bilty_print_config);
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function defaultBiltyPrintConfig(): array
    {
        return [
            'note' => '',
            'copies' => [
                'consignor' => [
                    'enabled' => true,
                    'show_amount_by_bob' => self::defaultShowAmountByBob(true),
                ],
                'consignee' => [
                    'enabled' => true,
                    'show_amount_by_bob' => self::defaultShowAmountByBob(false),
                ],
                'driver' => [
                    'enabled' => true,
                    'show_amount_by_bob' => self::defaultShowAmountByBob(false),
                ],
                'office' => [
                    'enabled' => true,
                    'show_amount_by_bob' => self::defaultShowAmountByBob(true),
                ],
            ],
        ];
    }

    /**
     * @return array{to_pay: bool, paid: bool, tbb: bool, foc: bool}
     */
    public static function defaultShowAmountByBob(bool $showAmount): array
    {
        if (! $showAmount) {
            return array_fill_keys(self::BOB_KEYS, false);
        }

        return [
            'to_pay' => true,
            'paid' => true,
            'tbb' => false,
            'foc' => true,
        ];
    }

    public function resolvedBiltyPrintConfig(): array
    {
        $defaults = self::defaultBiltyPrintConfig();
        $stored = is_array($this->bilty_print_config) ? $this->bilty_print_config : [];

        $config = [
            'note' => self::resolveBiltyPrintNote($stored),
            'copies' => [],
        ];

        foreach ($defaults['copies'] as $key => $defaultCopy) {
            $storedCopy = is_array($stored['copies'][$key] ?? null) ? $stored['copies'][$key] : [];

            $config['copies'][$key] = [
                'enabled' => (bool) ($storedCopy['enabled'] ?? $defaultCopy['enabled']),
                'show_amount_by_bob' => self::resolveShowAmountByBob($storedCopy, $defaultCopy),
            ];
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $storedCopy
     * @param  array<string, mixed>  $defaultCopy
     * @return array{to_pay: bool, paid: bool, tbb: bool, foc: bool}
     */
    public static function resolveShowAmountByBob(array $storedCopy, array $defaultCopy): array
    {
        $storedBob = is_array($storedCopy['show_amount_by_bob'] ?? null)
            ? $storedCopy['show_amount_by_bob']
            : null;

        if ($storedBob !== null) {
            return self::normalizeShowAmountByBob($storedBob);
        }

        if (array_key_exists('show_amount', $storedCopy)) {
            return self::defaultShowAmountByBob((bool) $storedCopy['show_amount']);
        }

        return $defaultCopy['show_amount_by_bob'];
    }

    /**
     * @param  array<string, mixed>  $bob
     * @return array{to_pay: bool, paid: bool, tbb: bool, foc: bool}
     */
    public static function normalizeShowAmountByBob(array $bob): array
    {
        $normalized = [];

        foreach (self::BOB_KEYS as $key) {
            $normalized[$key] = filter_var($bob[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $normalized;
    }

    public static function normalizeBiltyPrintConfig(?array $config): array
    {
        $defaults = self::defaultBiltyPrintConfig();
        $config = is_array($config) ? $config : [];

        $normalized = [
            'note' => self::resolveBiltyPrintNote($config),
            'copies' => [],
        ];

        foreach ($defaults['copies'] as $key => $defaultCopy) {
            $storedCopy = is_array($config['copies'][$key] ?? null) ? $config['copies'][$key] : [];

            $normalized['copies'][$key] = [
                'enabled' => filter_var($storedCopy['enabled'] ?? $defaultCopy['enabled'], FILTER_VALIDATE_BOOLEAN),
                'show_amount_by_bob' => self::resolveShowAmountByBob($storedCopy, $defaultCopy),
            ];
        }

        $enabledCount = collect($normalized['copies'])->filter(fn ($copy) => $copy['enabled'])->count();

        if ($enabledCount === 0) {
            abort(422, 'At least one bilty print copy must be enabled.');
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function biltyPrintConfigValidationRules(): array
    {
        $rules = [
            'bilty_print_config' => 'nullable|array',
            'bilty_print_config.note' => 'nullable|string|max:1000',
        ];

        foreach (self::COPY_KEYS as $copy) {
            $rules["bilty_print_config.copies.{$copy}.enabled"] = 'boolean';

            foreach (self::BOB_KEYS as $bob) {
                $rules["bilty_print_config.copies.{$copy}.show_amount_by_bob.{$bob}"] = 'boolean';
            }
        }

        return $rules;
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
}
