import type { CashflowPoint } from '@/types/dashboard.types';

export function transactionsUrlForCashflowBar(point: CashflowPoint): string {
    const params = new URLSearchParams({
        date_from: point.period_start,
        date_to: point.period_end,
    });

    return `/transactions?${params.toString()}`;
}
