import type { CategorySelectOption } from '@/types/category.types';

export type RecurringFrequency =
    | 'weekly'
    | 'biweekly'
    | 'monthly'
    | 'bimonthly'
    | 'quarterly'
    | 'biannual'
    | 'yearly';

export type RecurringSampleTransaction = {
    id: number;
    date: string;
    label: string;
    amount: string;
    account_name: string;
};

export type RecurringSuggestionRecord = {
    label_pattern: string;
    display_label: string;
    expected_amount: string;
    frequency: RecurringFrequency;
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
    occurrence_count: number;
    last_seen_at: string | null;
    category_id: number | null;
    category_name: string | null;
    category_color: string | null;
    account_name: string | null;
};

export type RecurringIndexPageProps = {
    suggestions: RecurringSuggestionRecord[];
    confirmed: ConfirmedRecurringPattern[];
    categoryOptions: CategorySelectOption[];
};
