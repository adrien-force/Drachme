import { format, parseISO } from 'date-fns';
import type { Locale } from 'date-fns';
import type { DashboardDateRange } from '@/types/dashboard.types';

export function formatDashboardDateRangeLabel(
    dateRange: DashboardDateRange,
    translate: (key: string) => string,
    dateLocale: Locale,
): string {
    if (dateRange.preset === 'custom') {
        return `${format(parseISO(dateRange.from), 'PP', { locale: dateLocale })} – ${format(parseISO(dateRange.to), 'PP', { locale: dateLocale })}`;
    }

    return translate(`dashboard.range_${dateRange.preset}`);
}
