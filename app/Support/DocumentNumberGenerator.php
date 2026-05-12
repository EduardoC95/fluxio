<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    public static function nextNumeric(string $modelClass, string $column = 'number'): int
    {
        /** @var class-string<Model> $modelClass */
        $last = DB::transaction(function () use ($modelClass, $column) {
            return $modelClass::query()
                ->select($column)
                ->orderByDesc($column)
                ->lockForUpdate()
                ->value($column);
        });

        return ((int) $last) + 1;
    }

    public static function nextDocument(string $modelClass, string $prefix, string $column = 'number', int $padding = 4): string
    {
        /** @var class-string<Model> $modelClass */
        $year = now()->format('Y');
        $pattern = sprintf('%s-%s-', $prefix, $year);

        $latest = DB::transaction(function () use ($modelClass, $column, $pattern) {
            return $modelClass::query()
                ->select($column)
                ->where($column, 'like', $pattern.'%')
                ->orderByDesc($column)
                ->lockForUpdate()
                ->value($column);
        });

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%s', $pattern, str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT));
    }
}
