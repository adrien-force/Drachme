import type { CategorySelectOption } from '@/types/category.types';

export type TransactionType = 'expense' | 'income' | 'transfer';

export type TransactionAccountOption = {
    id: number;
    name: string;
    logo_url: string | null;
};

export type TransactionTypeOption = {
    value: TransactionType;
    label: string;
};

export type TransactionCategorySummary = {
    id: number;
    name: string;
    color: string | null;
};

export type TransactionListItem = {
    id: number;
    account_id: number;
    account_name?: string;
    account_logo_url?: string | null;
    date: string;
    label: string;
    amount: number;
    type: TransactionType;
    notes?: string | null;
    is_transfer_linked: boolean;
    import_batch_id?: number | null;
    category_id: number | null;
    category_name?: string | null;
    category_color?: string | null;
};

export type AccountTransactionItem = {
    id: number;
    date: string;
    label: string;
    amount: number;
    type: TransactionType;
    is_transfer_linked: boolean;
    category_id: number | null;
    category_name?: string | null;
    category_color?: string | null;
};

export type TransactionsIndexPageProps = {
    transactions: TransactionListItem[];
    categoryOptions: CategorySelectOption[];
    filters: {
        category_id: string | null;
    };
    transactionEdit: TransactionsFormPageProps | null;
    uncategorizedCount: number;
};

export type TransactionsFormPageProps = {
    transaction: TransactionListItem | null;
    accounts: TransactionAccountOption[];
    presetAccountId: number | null;
    typeOptions: TransactionTypeOption[];
    categoryOptions: CategorySelectOption[];
    suggestedCategory: TransactionCategorySummary | null;
};
