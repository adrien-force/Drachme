/** Resolved chart token colors safe for SVG fill/stroke (no CSS var() in SVG attrs). */
const CHART_TOKEN_NAMES = [
    '--chart-1',
    '--chart-2',
    '--chart-3',
    '--chart-4',
    '--chart-5',
] as const;

const CHART_HEX_FALLBACK = [
    '#4ade80',
    '#f87171',
    '#94a3b8',
    '#c084fc',
    '#fb7185',
] as const;

export function resolveChartSliceColors(): string[] {
    if (typeof window === 'undefined') {
        return [...CHART_HEX_FALLBACK];
    }

    const root = getComputedStyle(document.documentElement);

    return CHART_TOKEN_NAMES.map((token, index) => {
        const resolved = root.getPropertyValue(token).trim();

        return resolved.length > 0 ? resolved : CHART_HEX_FALLBACK[index];
    });
}
