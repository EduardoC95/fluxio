<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    public static function nextNumeric(string $modelClass, string $column = 'number'): int
    {
        /** @var class-string<Model> $modelClass */
        return self::reserve(
            sprintf('%s:%s:numeric', $modelClass, $column),
            fn (): int => (int) $modelClass::query()->max($column),
        );
    }

    public static function nextDocument(string $modelClass, string $prefix, string $column = 'number', int $padding = 4): string
    {
        /** @var class-string<Model> $modelClass */
        $year = now()->format('Y');
        $pattern = sprintf('%s-%s-', $prefix, $year);

        $sequence = self::reserve(
            sprintf('%s:%s:%s:%s', $modelClass, $column, $prefix, $year),
            fn (): int => self::latestDocumentSequence($modelClass::query(), $column, $pattern),
        );

        return sprintf('%s%s', $pattern, str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT));
    }

    private static function latestDocumentSequence(Builder $query, string $column, string $pattern): int
    {
        $latest = $query
            ->select($column)
            ->where($column, 'like', $pattern.'%')
            ->orderByDesc($column)
            ->value($column);

        $sequence = 0;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = (int) $matches[1];
        }

        return $sequence;
    }

    private static function reserve(string $sequenceKey, callable $initialValue): int
    {
        return DB::transaction(function () use ($sequenceKey, $initialValue): int {
            DB::table('document_number_sequences')->insertOrIgnore([
                'sequence_key' => $sequenceKey,
                'last_number' => $initialValue(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('document_number_sequences')
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();

            $nextNumber = ((int) $sequence->last_number) + 1;

            DB::table('document_number_sequences')
                ->where('sequence_key', $sequenceKey)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return $nextNumber;
        }, 5);
    }
}
