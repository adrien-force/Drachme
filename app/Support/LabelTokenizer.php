<?php

declare(strict_types=1);

namespace App\Support;

final class LabelTokenizer
{
    /**
     * @return list<string>
     */
    public static function tokenize(string $label): array
    {
        $trimmed = trim($label);
        if ($trimmed === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $trimmed);

        if ($parts === false) {
            return [];
        }

        return array_values(array_filter(
            $parts,
            static fn (string $part): bool => $part !== '',
        ));
    }

    /**
     * @param  list<string>  $tokens
     */
    public static function patternFromTokens(array $tokens): string
    {
        $normalized = [];
        foreach ($tokens as $token) {
            $value = mb_strtolower(trim($token));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return implode(' ', $normalized);
    }
}
