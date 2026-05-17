import type {
    RecurringFlow,
    RecurringFrequency,
    RecurringListFilters,
    RecurringSortColumn,
} from '@/types/recurring.types';

type RecurringQueryPatch = Partial<{
    search: string;
    flow: RecurringFlow | null;
    frequency: RecurringFrequency | null;
    sort: RecurringSortColumn;
    order: 'asc' | 'desc';
    per_page: number;
    confirmed_page: number;
    suggestions_page: number;
}>;

export function recurringIndexQuery(
    filters: RecurringListFilters,
    patch: RecurringQueryPatch = {},
): Record<string, string | number> {
    const next: RecurringListFilters = { ...filters, ...patch };

    const query: Record<string, string | number> = {
        sort: next.sort,
        order: next.order,
        per_page: next.per_page,
        confirmed_page: next.confirmed_page,
        suggestions_page: next.suggestions_page,
    };

    if (next.search.trim() !== '') {
        query.search = next.search.trim();
    }

    if (next.flow !== null) {
        query.flow = next.flow;
    }

    if (next.frequency !== null) {
        query.frequency = next.frequency;
    }

    return query;
}

export function recurringFiltersWithDefaults(
    patch: RecurringQueryPatch,
): RecurringQueryPatch {
    return {
        confirmed_page: 1,
        suggestions_page: 1,
        ...patch,
    };
}
