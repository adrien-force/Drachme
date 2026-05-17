import type { PaginatedMeta } from '@/types/account.types';
import type { CategorySelectOption } from '@/types/category.types';
import type { TransactionType } from '@/types/transaction.types';

export type RecurringFrequency =
    | 'weekly'
    | 'biweekly'
    | 'monthly'
    | 'bimonthly'
    | 'quarterly'
    | 'biannual'
    | 'yearly';

export type RecurringFlow = 'credit' | 'debit';

export type RecurringSortColumn = 'label' | 'amount' | 'frequency' | 'occurrences' | 'last_seen';

export type RecurringSampleTransaction = {
    id: number;
    date: string;
    label: string;
    amount: string;
    type: TransactionType;
    account_name: string;
};

export type RecurringSuggestionRecord = {
    label_pattern: string;
    display_label: string;
    expected_amount: string;
    frequency: RecurringFrequency;
    transaction_type: TransactionType;
    signed_amount: string;
    monthly_amount: string;
    occurrence_count: number;
    score: number;
    suggested_category_id: number | null;
    account_id: number | null;
    samples: RecurringSampleTransaction[];
};

export type ConfirmedRecurringPattern = {
    id: number;
    label_pattern: string;
    display_label: string;
    expected_amount: string;
    frequency: RecurringFrequency;
    transaction_type: TransactionType;
    signed_amount: string;
    monthly_amount: string;
    occurrence_count: number;
    last_seen_at: string | null;
    category_id: number | null;
    category_name: string | null;
    category_color: string | null;
    account_name: string | null;
};

export type RecurringSummaryFrequencySlice = {
    frequency: RecurringFrequency;
    count: number;
};

export type RecurringSummaryTopItem = {
    label: string;
    monthly_amount: string;
    transaction_type: TransactionType;
};

export type RecurringSummary = {
    monthly_expense: string;
    monthly_income: string;
    confirmed_count: number;
    expense_count: number;
    income_count: number;
    by_frequency: RecurringSummaryFrequencySlice[];
    top_items: RecurringSummaryTopItem[];
};

export type RecurringListFilters = {
    search: string;
    flow: RecurringFlow | null;
    frequency: RecurringFrequency | null;
    sort: RecurringSortColumn;
    order: 'asc' | 'desc';
    per_page: number;
    confirmed_page: number;
    suggestions_page: number;
};

export type PaginatedRecurring<T> = {
    data: T[];
    meta: PaginatedMeta;
};

export type RecurringIndexPageProps = {
    suggestions: PaginatedRecurring<RecurringSuggestionRecord>;
    confirmed: PaginatedRecurring<ConfirmedRecurringPattern>;
    summary: RecurringSummary;
    filters: RecurringListFilters;
    perPageOptions: number[];
    frequencyOptions: RecurringFrequency[];
    categoryOptions: CategorySelectOption[];
};
