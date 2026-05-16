<?php

declare(strict_types=1);

namespace App\Support;

class SignedAmountParser
{
    /**
     * Parse amounts with explicit sign (+50, -50), European decimals, and accounting parentheses.
     */
    public function parse(string $value): float
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return 0.0;
        }

        $negative = false;

        if (preg_match('/^\((.+)\)$/u', $trimmed, $matches) === 1) {
            $negative = true;
            $trimmed = trim($matches[1]);
        }

        if (str_starts_with($trimmed, '-')) {
            $negative = true;
            $trimmed = ltrim($trimmed, '-');
        } elseif (str_starts_with($trimmed, '+')) {
            $trimmed = ltrim($trimmed, '+');
        }

        $normalized = str_replace([' ', "\u{00A0}"], '', $trimmed);
        $normalized = str_replace(',', '.', $normalized);
        $number = (float) $normalized;

        return $negative ? -abs($number) : $number;
    }
}
