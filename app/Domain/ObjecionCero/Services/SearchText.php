<?php

namespace App\Domain\ObjecionCero\Services;

use Illuminate\Support\Str;

class SearchText
{
    public static function matches(string $query, ?string ...$values): bool
    {
        $needle = self::normalize($query);

        if ($needle === '') {
            return true;
        }

        foreach ($values as $value) {
            if (str_contains(self::normalize($value ?? ''), $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(string $value): string
    {
        $value = Str::lower(Str::transliterate($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
