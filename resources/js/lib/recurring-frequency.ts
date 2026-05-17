import type { RecurringFrequency } from '@/types/recurring.types';

export const RECURRING_FREQUENCIES: RecurringFrequency[] = [
    'weekly',
    'biweekly',
    'monthly',
    'bimonthly',
    'quarterly',
    'biannual',
    'yearly',
];

export function recurringFrequencyLabel(
    frequency: RecurringFrequency,
    t: (key: string) => string,
): string {
    return t(`recurring.frequency_${frequency}`);
}
