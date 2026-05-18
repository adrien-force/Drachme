<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class SettlementLabelMatcher
{
    /**
     * @return list<string>
     */
    public static function patternsFromAccount(?string $pattern): array
    {
        if ($pattern === null || trim($pattern) === '') {
            return [];
        }

        $parts = preg_split('/[|;]/', $pattern) ?: [];

        $normalized = [];

        foreach ($parts as $part) {
            $token = Str::upper(trim($part));

            if ($token !== '') {
                $normalized[] = $token;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Whether a bank label matches user-defined settlement pattern(s).
     */
    public static function matches(?string $pattern, string $label): bool
    {
        $patterns = self::patternsFromAccount($pattern);

        if ($patterns === []) {
            return false;
        }

        $haystack = Str::upper(trim($label));

        if ($haystack === '') {
            return false;
        }

        foreach ($patterns as $needle) {
            if (Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true when the label looks like a generic card settlement (no custom pattern).
     */
    public static function matchesGenericKeywords(string $label): bool
    {
        $normalized = Str::upper(trim($label));

        if ($normalized === '') {
            return false;
        }

        foreach (
            [
                'DEBIT DIFFERE',
                'DEBIT DIFFÉRÉ',
                'PRELEVEMENT CB',
                'PRLV CB',
                'CARTE',
                'CREDIT CARD',
                'VISA',
                'MASTERCARD',
                'AMEX',
            ] as $keyword
        ) {
            if (Str::contains($normalized, $keyword)) {
                return true;
            }
        }

        return preg_match('/\bCB\b/u', $normalized) === 1;
    }
}
