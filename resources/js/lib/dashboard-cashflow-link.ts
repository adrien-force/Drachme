import type { CashflowPoint } from '@/types/dashboard.types';
import type { TransactionFlow } from '@/types/account.types';

export function transactionsUrlForCashflowBar(
    point: CashflowPoint,
    flow: TransactionFlow,
): string {
    const params = new URLSearchParams({
        date_from: point.period_start,
        date_to: point.period_end,
        flow,
    });

    return `/transactions?${params.toString()}`;
}
