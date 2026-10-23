<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    public static function next(Builder $query, string $column, ?string $initial = null, int $defaultPadLength = 4): string
    {
        $initial = $initial ?? '';

        if ($initial === '') {
            $last = (clone $query)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->where(fn (Builder $builder) => self::whereNumericColumn($builder, $column))
                ->orderByDesc('id')
                ->value($column);

            $padLength = $last !== null ? strlen((string) $last) : $defaultPadLength;

            $maxSequence = (clone $query)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->where(fn (Builder $builder) => self::whereNumericColumn($builder, $column))
                ->selectRaw(self::maxCastExpression($column).' as max_seq')
                ->value('max_seq');

            $next = ((int) ($maxSequence ?? 0)) + 1;

            return str_pad((string) $next, $padLength, '0', STR_PAD_LEFT);
        }

        $prefixLength = strlen($initial);

        $last = (clone $query)
            ->where($column, 'like', $initial.'%')
            ->where(fn (Builder $builder) => self::whereNumericSuffix($builder, $column, $prefixLength))
            ->orderByDesc('id')
            ->value($column);

        $padLength = $defaultPadLength;
        if ($last !== null) {
            $padLength = strlen(substr((string) $last, $prefixLength));
        }

        $maxSequence = (clone $query)
            ->where($column, 'like', $initial.'%')
            ->where(fn (Builder $builder) => self::whereNumericSuffix($builder, $column, $prefixLength))
            ->selectRaw(self::maxSuffixCastExpression($column).' as max_seq', [$prefixLength + 1])
            ->value('max_seq');

        $next = ((int) ($maxSequence ?? 0)) + 1;

        return $initial.str_pad((string) $next, $padLength, '0', STR_PAD_LEFT);
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    private static function whereNumericColumn(Builder $query, string $column): void
    {
        if (self::driver() === 'sqlite') {
            $query->whereRaw("{$column} GLOB '[0-9]*' AND length({$column}) > 0");

            return;
        }

        $query->whereRaw("{$column} REGEXP '^[0-9]+$'");
    }

    private static function whereNumericSuffix(Builder $query, string $column, int $prefixLength): void
    {
        if (self::driver() === 'sqlite') {
            $query->whereRaw(
                "substr({$column}, ?) GLOB '[0-9]*' AND length(substr({$column}, ?)) > 0",
                [$prefixLength + 1, $prefixLength + 1]
            );

            return;
        }

        $query->whereRaw("SUBSTRING({$column}, ?) REGEXP '^[0-9]+$'", [$prefixLength + 1]);
    }

    private static function maxCastExpression(string $column): string
    {
        if (self::driver() === 'sqlite') {
            return "MAX(CAST({$column} AS INTEGER))";
        }

        return "MAX(CAST({$column} AS UNSIGNED))";
    }

    private static function maxSuffixCastExpression(string $column): string
    {
        if (self::driver() === 'sqlite') {
            return "MAX(CAST(substr({$column}, ?) AS INTEGER))";
        }

        return "MAX(CAST(SUBSTRING({$column}, ?) AS UNSIGNED))";
    }
}
