import type {
    AccountBalanceHistory,
    AccountTransactionFilters,
} from '@/types/account.types';
import type { TransactionListFilters } from '@/types/transaction.types';

import { buildAccountShowQuery } from '@/lib/account-show-query';
import { buildTransactionsIndexQuery } from '@/lib/transactions-index-query';

export function accountTransactionEditUrl(
    accountId: number,
    transactionId: number,
    balanceHistory: Pick<AccountBalanceHistory, 'from' | 'to' | 'is_all_time'> | null,
    filters: AccountTransactionFilters,
): string {
    const query = buildAccountShowQuery(balanceHistory, filters, transactionId);

    return `/accounts/${accountId}?${new URLSearchParams(
        Object.entries(query).map(([key, value]) => [key, String(value)]),
    ).toString()}`;
}

export function transactionsIndexEditUrl(
    transactionId: number,
    filters: TransactionListFilters,
): string {
    const query = buildTransactionsIndexQuery(filters, transactionId);

    return `/transactions?${new URLSearchParams(
        Object.entries(query).map(([key, value]) => [key, String(value)]),
    ).toString()}`;
}
