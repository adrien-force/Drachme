<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class RecurringLabelNormalizer
{
    /**
     * Normalized labels too generic to suggest as recurring (French banking noise).
     *
     * @var list<string>
     */
    private const GENERIC_LABELS = [
        'CB',
        'CARTE',
        'PAIEMENT CB',
        'PAIEMENT PAR CARTE',
        'RETRAIT DAB',
        'RETRAIT',
        'VIREMENT',
        'VIR',
        'PRELEVEMENT',
        'PRELEV',
        'REMISE',
        'ACHAT',
        'PAIEMENT',
    ];

    public function normalize(string $label): string
    {
        $normalized = Str::upper(Str::ascii($label));
        $normalized = preg_replace('/\d+/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^A-Z\s]/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', trim($normalized)) ?? $normalized;

        return $normalized;
    }

    public function isGeneric(string $normalizedLabel): bool
    {
        if ($normalizedLabel === '' || mb_strlen($normalizedLabel) < 4) {
            return true;
        }

        foreach (self::GENERIC_LABELS as $generic) {
            if ($normalizedLabel === $generic || str_starts_with($normalizedLabel, $generic.' ')) {
                return true;
            }
        }

        return false;
    }
}
