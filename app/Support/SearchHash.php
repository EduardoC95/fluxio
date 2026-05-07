<?php

namespace App\Support;

use Illuminate\Support\Str;

class SearchHash
{
    public static function make(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->value();

        return hash('sha256', $normalized);
    }
}
