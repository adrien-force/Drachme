<?php

namespace App\Support;

use App\Models\User;

class ThemeColors
{
    /** @var list<string> */
    public const KEYS = [
        'primary',
        'chart_income',
        'chart_expense',
        'chart_net_worth',
        'chart_secondary',
    ];

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        /** @var array<string, string> $defaults */
        $defaults = config('drachme-theme.defaults');

        return $defaults;
    }

    /**
     * @return array<string, string>
     */
    public static function resolve(?User $user): array
    {
        $merged = self::defaults();

        if ($user === null) {
            return $merged;
        }

        $custom = $user->theme_colors;

        if (! is_array($custom)) {
            return $merged;
        }

        foreach (self::KEYS as $key) {
            if (isset($custom[$key]) && is_string($custom[$key]) && self::isValidHex($custom[$key])) {
                $merged[$key] = strtolower($custom[$key]);
            }
        }

        return $merged;
    }

    /**
     * CSS custom properties applied on :root / .dark.
     *
     * @param  array<string, string>  $colors
     * @return array<string, string>
     */
    public static function cssVariables(array $colors): array
    {
        return [
            '--primary' => $colors['primary'],
            '--ring' => $colors['primary'],
            '--chart-income' => $colors['chart_income'],
            '--chart-expense' => $colors['chart_expense'],
            '--chart-net-worth' => $colors['chart_net_worth'],
            '--chart-secondary' => $colors['chart_secondary'],
            '--chart-1' => $colors['chart_net_worth'],
            '--chart-2' => $colors['chart_expense'],
        ];
    }

    public static function isValidHex(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }
}
