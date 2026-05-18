import type { CategorySelectOption } from '@/types/category.types';
import type { RecurringFrequency, TransactionType } from '@/types/transaction.types';

export type TriageTransaction = {
    id: number;
    date: string;
    label: string;
    amount: number;
    type: TransactionType;
    account_id: number;
    account_name: string | null;
    account_logo_url: string | null;
    account_type: string | null;
    label_tokens: string[];
    suggested_category_id: number | null;
    suggested_category_name: string | null;
    suggested_category_color: string | null;
    is_card_settlement: boolean;
};

export type TransactionsTriagePageProps = {
    transaction: TriageTransaction | null;
    remaining: number;
    totalUncategorized: number;
    skipIds: number[];
    categoryOptions: CategorySelectOption[];
};
