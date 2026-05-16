import type {
    AccountBalanceHistory,
    AccountTransactionFilters,
} from '@/types/account.types';

type ChartQuery = Pick<AccountBalanceHistory, 'from' | 'to' | 'is_all_time'>;

export function buildAccountShowQuery(
    chart: ChartQuery,
    filters: AccountTransactionFilters,
): Record<string, string | number> {
    const query: Record<string, string | number> = {
        sort: filters.sort,
        order: filters.order,
        per_page: filters.per_page,
        page: filters.page,
    };

    if (chart.is_all_time) {
        query.chart_all_time = 1;
    } else {
        query.from = chart.from;
        query.to = chart.to;
    }

    if (filters.search.trim() !== '') {
        query.search = filters.search.trim();
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

    return query;
}
