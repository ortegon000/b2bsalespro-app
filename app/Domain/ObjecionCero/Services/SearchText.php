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
            $value ??= '';

            if (
                str_contains(self::normalize($value), $needle)
                || self::wordsAppearInOrder($query, $value)
            ) {
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

    private static function wordsAppearInOrder(string $query, string $value): bool
    {
        $queryWords = self::words($query);

        if (count($queryWords) < 2) {
            return false;
        }

        $valueWords = self::words($value);
        $queryPosition = 0;

        foreach ($valueWords as $valueWord) {
            if ($valueWord !== $queryWords[$queryPosition]) {
                continue;
            }

            $queryPosition++;

            if ($queryPosition === count($queryWords)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function words(string $value): array
    {
        $value = Str::lower(Str::transliterate($value));

        return preg_split('/[^a-z0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
