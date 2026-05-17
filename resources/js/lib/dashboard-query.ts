import { dashboard } from '@/routes';
import type { DashboardDateRange } from '@/types/dashboard.types';

export function buildDashboardQuery(
    range: Pick<DashboardDateRange, 'preset' | 'from' | 'to'>,
): Record<string, string> {
    if (range.preset === 'custom') {
        return {
            from: range.from,
            to: range.to,
        };
    }

    return {
        preset: range.preset,
    };
}

export function dashboardUrl(
    range: Pick<DashboardDateRange, 'preset' | 'from' | 'to'>,
): string {
    return dashboard.url({ query: buildDashboardQuery(range) });
}
