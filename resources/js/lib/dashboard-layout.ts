/** Vertical space below app header (h-14 + mt-4 + buffer). */
export const DASHBOARD_VIEWPORT_HEIGHT =
    'h-[calc(100dvh-5.25rem)] max-h-[calc(100dvh-5.25rem)]';

/** KPI row sizes to content; charts fill the rest. */
export const DASHBOARD_ROWS_WITH_BANNER =
    'grid-rows-[auto_auto_minmax(0,1fr)]' as const;

export const DASHBOARD_ROWS_DEFAULT = 'grid-rows-[auto_minmax(0,1fr)]' as const;

export function dashboardChartsGridClass(panelCount: number): string {
    const base = 'grid min-h-0 h-full gap-3 md:gap-4';

    if (panelCount <= 2) {
        return `${base} grid-cols-1 lg:grid-cols-2 lg:grid-rows-1`;
    }

    return `${base} grid-cols-1 lg:grid-cols-2 lg:grid-rows-2`;
}
