import type { TransactionListFilters } from '@/types/transaction.types';

export function buildTransactionsIndexQuery(
    filters: TransactionListFilters,
    editTransactionId?: number,
): Record<string, string | number> {
    const query: Record<string, string | number> = {
        sort: filters.sort,
        order: filters.order,
        per_page: filters.per_page,
        page: filters.page,
    };

    const search = filters.search.trim();
    if (search !== '') {
        query.search = search;
    }

    if (filters.date_from) {
        query.date_from = filters.date_from;
    }

    if (filters.date_to) {
        query.date_to = filters.date_to;
    }

    if (filters.type) {
        query.type = filters.type;
    }

    if (filters.flow) {
        query.flow = filters.flow;
    }

    if (filters.category_id) {
        query.category_id = filters.category_id;
    }

    if (filters.account_id !== null) {
        query.account_id = filters.account_id;
    }

    if (filters.amount_min !== null && filters.amount_min !== '') {
        query.amount_min = filters.amount_min;
    }

    if (filters.amount_max !== null && filters.amount_max !== '') {
        query.amount_max = filters.amount_max;
    }

    if (editTransactionId !== undefined) {
        query.edit_transaction = editTransactionId;
    }

    return query;
}
