<?php

declare(strict_types=1);

namespace App\Support;

final class Utf8Normalizer
{
    public static function ensureValid(string $value): string
    {
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $fromEncoding) {
            $converted = iconv($fromEncoding, 'UTF-8//IGNORE', $value);

            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        $stripped = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return $stripped !== false ? $stripped : '';
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public static function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? self::ensureValid($key) : $key;

            $sanitized[$normalizedKey] = match (true) {
                is_array($value) => self::sanitizeArray($value),
                is_string($value) => self::ensureValid($value),
                default => $value,
            };
        }

        return $sanitized;
    }
}
