<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use InvalidArgumentException;

class DateFormatDetector
{
    /**
     * @var list<array{format: string, label: string}>
     */
    private const CANDIDATES = [
        ['format' => 'Y-m-d H:i:s', 'label' => 'AAAA-MM-JJ HH:MM:SS'],
        ['format' => 'Y-m-d H:i', 'label' => 'AAAA-MM-JJ HH:MM'],
        ['format' => 'Y-m-d', 'label' => 'AAAA-MM-JJ'],
        ['format' => 'd/m/Y H:i:s', 'label' => 'JJ/MM/AAAA HH:MM:SS'],
        ['format' => 'd/m/Y H:i', 'label' => 'JJ/MM/AAAA HH:MM'],
        ['format' => 'd/m/Y', 'label' => 'JJ/MM/AAAA'],
        ['format' => 'd-m-Y H:i:s', 'label' => 'JJ-MM-AAAA HH:MM:SS'],
        ['format' => 'd-m-Y', 'label' => 'JJ-MM-AAAA'],
        ['format' => 'd.m.Y H:i:s', 'label' => 'JJ.MM.AAAA HH:MM:SS'],
        ['format' => 'd.m.Y', 'label' => 'JJ.MM.AAAA'],
        ['format' => 'Y/m/d H:i:s', 'label' => 'AAAA/MM/JJ HH:MM:SS'],
        ['format' => 'Y/m/d', 'label' => 'AAAA/MM/JJ'],
        ['format' => 'm/d/Y H:i:s', 'label' => 'MM/JJ/AAAA HH:MM:SS'],
        ['format' => 'm/d/Y', 'label' => 'MM/JJ/AAAA'],
        ['format' => 'd/m/y H:i:s', 'label' => 'JJ/MM/AA HH:MM:SS'],
        ['format' => 'd/m/y', 'label' => 'JJ/MM/AA'],
    ];

    /**
     * @param  list<string|null>  $samples
     * @return array{
     *     format: string,
     *     label: string,
     *     matched: int,
     *     total: int,
     *     confidence: float,
     * }|null
     */
    public function detect(array $samples): ?array
    {
        $values = array_values(array_filter(
            array_map(static fn ($value) => trim((string) $value), $samples),
            static fn (string $value): bool => $value !== '',
        ));

        if ($values === []) {
            return null;
        }

        $best = null;
        $bestMatched = 0;

        foreach (self::CANDIDATES as $candidate) {
            $matched = 0;

            foreach ($values as $value) {
                if ($this->matchesFormat($value, $candidate['format'])) {
                    $matched++;
                }
            }

            if ($matched > $bestMatched) {
                $bestMatched = $matched;
                $best = $candidate;
            }
        }

        if ($best === null || $bestMatched === 0) {
            return null;
        }

        $total = count($values);

        return [
            'format' => $best['format'],
            'label' => $best['label'],
            'matched' => $bestMatched,
            'total' => $total,
            'confidence' => round($bestMatched / $total, 2),
        ];
    }

    public function parse(string $value, string $format): CarbonImmutable
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException(ImportRowError::dateEmpty());
        }

        if (! $this->matchesFormat($trimmed, $format)) {
            throw new InvalidArgumentException(
                ImportRowError::dateFormatMismatch($trimmed, $format),
            );
        }

        try {
            $parsed = CarbonImmutable::createFromFormat($format, $trimmed);
        } catch (InvalidFormatException) {
            throw new InvalidArgumentException(
                ImportRowError::dateParseFailed($trimmed, $format),
            );
        }

        if (! $parsed instanceof CarbonImmutable) {
            throw new InvalidArgumentException(
                ImportRowError::dateParseFailed($trimmed, $format),
            );
        }

        return self::formatExpectsTime($format)
            ? $parsed
            : $parsed->startOfDay();
    }

    private static function formatExpectsTime(string $format): bool
    {
        return str_contains($format, 'H') || str_contains($format, 'h');
    }

    private function matchesFormat(string $value, string $format): bool
    {
        if (CarbonImmutable::hasFormat($value, $format)) {
            return true;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat($format, $value);
        } catch (InvalidFormatException) {
            return false;
        }

        if (! $parsed instanceof CarbonImmutable) {
            return false;
        }

        return $parsed->format($format) === $value;
    }
}
