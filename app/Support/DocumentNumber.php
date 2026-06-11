<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class DocumentNumber
{
    public static function next(Builder $query, string $column, ?string $initial = null, int $defaultPadLength = 4): string
    {
        $initial = $initial ?? '';

        if ($initial === '') {
            $last = (clone $query)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->whereRaw("{$column} REGEXP '^[0-9]+$'")
                ->orderByDesc('id')
                ->value($column);

            $padLength = $last !== null ? strlen((string) $last) : $defaultPadLength;

            $maxSequence = (clone $query)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->whereRaw("{$column} REGEXP '^[0-9]+$'")
                ->selectRaw("MAX(CAST({$column} AS UNSIGNED)) as max_seq")
                ->value('max_seq');

            $next = ((int) ($maxSequence ?? 0)) + 1;

            return str_pad((string) $next, $padLength, '0', STR_PAD_LEFT);
        }

        $prefixLength = strlen($initial);

        $last = (clone $query)
            ->where($column, 'like', $initial.'%')
            ->whereRaw("SUBSTRING({$column}, ?) REGEXP '^[0-9]+$'", [$prefixLength + 1])
            ->orderByDesc('id')
            ->value($column);

        $padLength = $defaultPadLength;
        if ($last !== null) {
            $padLength = strlen(substr((string) $last, $prefixLength));
        }

        $maxSequence = (clone $query)
            ->where($column, 'like', $initial.'%')
            ->whereRaw("SUBSTRING({$column}, ?) REGEXP '^[0-9]+$'", [$prefixLength + 1])
            ->selectRaw("MAX(CAST(SUBSTRING({$column}, ?) AS UNSIGNED)) as max_seq", [$prefixLength + 1])
            ->value('max_seq');

        $next = ((int) ($maxSequence ?? 0)) + 1;

        return $initial.str_pad((string) $next, $padLength, '0', STR_PAD_LEFT);
    }
}
