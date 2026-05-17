import type {
    AccountBalanceHistory,
    AccountTransactionFilters,
} from '@/types/account.types';

import { buildAccountShowQuery } from '@/lib/account-show-query';

export function accountTransactionEditUrl(
    accountId: number,
    transactionId: number,
    balanceHistory: Pick<AccountBalanceHistory, 'from' | 'to' | 'is_all_time'>,
    filters: AccountTransactionFilters,
): string {
    const query = buildAccountShowQuery(balanceHistory, filters, transactionId);

    return `/accounts/${accountId}?${new URLSearchParams(
        Object.entries(query).map(([key, value]) => [key, String(value)]),
    ).toString()}`;
}

export function transactionsIndexEditUrl(
    transactionId: number,
    filters: { category_id: string | null },
): string {
    const params = new URLSearchParams();
    params.set('edit_transaction', String(transactionId));

    if (filters.category_id) {
        params.set('category_id', filters.category_id);
    }

    return `/transactions?${params.toString()}`;
}
