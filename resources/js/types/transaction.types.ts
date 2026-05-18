import type { AccountType } from '@/types/account.types';
import type { CategorySelectOption } from '@/types/category.types';
import type {
    PaginatedMeta,
    SortOrder,
    TransactionFlow,
    TransactionSortColumn,
} from '@/types/account.types';

export type TransactionType = 'expense' | 'income' | 'transfer';

export type TransactionAccountOption = {
    id: number;
    name: string;
    logo_url: string | null;
    type?: AccountType;
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
    recurring_pattern_id?: number | null;
    recurring_display_label?: string | null;
    account_type?: AccountType | null;
    is_card_settlement?: boolean;
    card_period_start?: string | null;
};

export type AccountTransactionItem = {
    id: number;
    date: string;
    label: string;
    amount: number;
    type: TransactionType;
    is_transfer_linked: boolean;
    is_card_settlement?: boolean;
    category_id: number | null;
    category_name?: string | null;
    category_color?: string | null;
};

export type TransactionListFilters = {
    search: string;
    date_from: string | null;
    date_to: string | null;
    type: TransactionType | null;
    flow: TransactionFlow | null;
    category_id: string | null;
    account_id: number | null;
    amount_min: string | null;
    amount_max: string | null;
    sort: TransactionSortColumn;
    order: SortOrder;
    per_page: number;
    page: number;
};

export type PaginatedTransactions = {
    data: TransactionListItem[];
    meta: PaginatedMeta;
};

export type TransactionListSummary = {
    count: number;
    amount_total: number;
};

export type TransactionSankeyNode = {
    name: string;
    category: 'source' | 'outcome';
    color: string | null;
    kind: 'account' | 'category';
};

export type TransactionSankeyFlow = {
    nodes: TransactionSankeyNode[];
    links: Array<{
        source: number;
        target: number;
        value: number;
    }>;
};

export type TransactionsIndexPageProps = {
    transactions: PaginatedTransactions;
    listSummary: TransactionListSummary;
    sankeyFlow: TransactionSankeyFlow;
    categoryOptions: CategorySelectOption[];
    accountOptions: TransactionAccountOption[];
    filters: TransactionListFilters;
    transactionEdit: TransactionsFormPageProps | null;
    uncategorizedCount: number;
    perPageOptions: number[];
    typeOptions: TransactionTypeOption[];
};

export type TransactionsFormPageProps = {
    transaction: TransactionListItem | null;
    accounts: TransactionAccountOption[];
    presetAccountId: number | null;
    typeOptions: TransactionTypeOption[];
    categoryOptions: CategorySelectOption[];
    suggestedCategory: TransactionCategorySummary | null;
};
