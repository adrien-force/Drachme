import type { ThemeColorMap } from '@/types/theme.types';

export const DEFAULT_THEME_COLORS: ThemeColorMap = {
    primary: '#4ade80',
    chart_income: '#4ade80',
    chart_expense: '#f87171',
    chart_net_worth: '#4ade80',
    chart_secondary: '#94a3b8',
};

const STORAGE_KEY = 'drachme_theme_colors';
const COOKIE_KEY = 'drachme_theme_colors';

export const THEME_COLOR_CSS_MAP: Record<keyof ThemeColorMap, string[]> = {
    primary: ['--primary', '--ring'],
    chart_income: ['--chart-income'],
    chart_expense: ['--chart-expense', '--chart-2', '--destructive'],
    chart_net_worth: ['--chart-net-worth', '--chart-1'],
    chart_secondary: ['--chart-secondary', '--chart-3'],
};

export function applyThemeColors(colors: ThemeColorMap): void {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    (Object.keys(THEME_COLOR_CSS_MAP) as Array<keyof ThemeColorMap>).forEach(
        (key) => {
            const value = colors[key];
            const variables = new Set(THEME_COLOR_CSS_MAP[key]);

            variables.forEach((cssVar) => {
                root.style.setProperty(cssVar, value);
            });
        },
    );

    root.style.setProperty(
        '--primary-foreground',
        'oklch(0.145 0 0)',
    );
}

export function persistThemeColors(colors: ThemeColorMap): void {
    if (typeof window === 'undefined') {
        return;
    }

    const payload = JSON.stringify(colors);
    localStorage.setItem(STORAGE_KEY, payload);

    const maxAge = 365 * 24 * 60 * 60;
    document.cookie = `${COOKIE_KEY}=${encodeURIComponent(payload)};path=/;max-age=${maxAge};SameSite=Lax`;
}

export function readStoredThemeColors(): ThemeColorMap | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const raw =
        localStorage.getItem(STORAGE_KEY) ??
        readThemeColorsCookie();

    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw) as ThemeColorMap;
    } catch {
        return null;
    }
}

function readThemeColorsCookie(): string | null {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith(`${COOKIE_KEY}=`));

    if (!match) {
        return null;
    }

    return decodeURIComponent(match.split('=').slice(1).join('='));
}

export function initializeThemeColors(fallback: ThemeColorMap): void {
    const stored = readStoredThemeColors();
    applyThemeColors(stored ?? fallback);

    if (!stored) {
        persistThemeColors(fallback);
    }
}
