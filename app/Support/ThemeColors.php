<?php

declare(strict_types=1);


namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use JsonException;

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
            '--chart-3' => $colors['chart_secondary'],
            '--destructive' => $colors['chart_expense'],
        ];
    }

    /**
     * Resolve theme colors for the current HTTP request (user DB + cookie fallback).
     *
     * @return array<string, string>
     */
    public static function resolveForRequest(Request $request): array
    {
        $user = $request->user();

        if ($user !== null && is_array($user->theme_colors)) {
            return self::resolve($user);
        }

        return self::mergeCookieColors(
            self::resolve($user),
            $request->cookie('drachme_theme_colors'),
        );
    }

    /**
     * @param  array<string, string>  $colors
     * @return array<string, string>
     */
    private static function mergeCookieColors(array $colors, mixed $cookie): array
    {
        if (! is_string($cookie) || $cookie === '') {
            return $colors;
        }

        try {
            $decoded = json_decode($cookie, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $colors;
        }

        if (! is_array($decoded)) {
            return $colors;
        }

        foreach (self::KEYS as $key) {
            if (! isset($decoded[$key]) || ! is_string($decoded[$key])) {
                continue;
            }

            if (self::isValidHex($decoded[$key])) {
                $colors[$key] = strtolower($decoded[$key]);
            }
        }

        return $colors;
    }

    /**
     * Inline CSS declarations for html.dark (SSR first paint).
     *
     * @param  array<string, string>  $colors
     */
    public static function inlineStyleDeclarations(array $colors): string
    {
        $declarations = [];
        foreach (self::cssVariables($colors) as $property => $value) {
            $declarations[] = "{$property}: {$value};";
        }

        return implode("\n                ", $declarations);
    }

    public static function isValidHex(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }
}
