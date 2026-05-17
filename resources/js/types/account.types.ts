import type { CategorySelectOption } from '@/types/category.types';
import type { TransactionsFormPageProps } from '@/types/transaction.types';
import type {
    AccountTransactionItem,
    TransactionType,
    TransactionTypeOption,
} from '@/types/transaction.types';

export type AccountType =
    | 'checking'
    | 'savings'
    | 'invest'
    | 'credit'
    | 'cash';

export type AccountRecord = {
    id: number;
    name: string;
    institution: string | null;
    logo_url: string | null;
    type: AccountType;
    initial_balance: number;
    current_balance: number;
    currency: string;
    opened_at: string | null;
    is_archived: boolean;
    last_activity_at: string | null;
};

export type AccountTypeOption = {
    value: AccountType;
    label: string;
};

export type AccountsIndexFilters = {
    archived: boolean;
};

export type AccountsIndexPageProps = {
    accounts: AccountRecord[];
    filters: AccountsIndexFilters;
    accountTypes: AccountTypeOption[];
};

export type AccountBalancePoint = {
    date: string;
    balance: number;
};

export type AccountBalanceHistory = {
    from: string;
    to: string;
    points: AccountBalancePoint[];
    is_all_time: boolean;
};

export type TransactionFlow = 'credit' | 'debit';

export type TransactionSortColumn =
    | 'date'
    | 'label'
    | 'amount'
    | 'type'
    | 'category'
    | 'account';

export type SortOrder = 'asc' | 'desc';

export type AccountTransactionFilters = {
    search: string;
    date_from: string | null;
    date_to: string | null;
    type: TransactionType | null;
    flow: TransactionFlow | null;
    category_id: string | null;
    sort: TransactionSortColumn;
    order: SortOrder;
    per_page: number;
    page: number;
};

export type PaginatedMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type PaginatedTransactions = {
    data: AccountTransactionItem[];
    meta: PaginatedMeta;
};

export type AccountsShowPageProps = {
    account: AccountRecord;
    transactions: PaginatedTransactions;
    transactionFilters: AccountTransactionFilters;
    transactionTypeOptions: TransactionTypeOption[];
    categoryOptions: CategorySelectOption[];
    perPageOptions: number[];
    balanceHistory: AccountBalanceHistory;
    transactionEdit: TransactionsFormPageProps | null;
    uncategorizedCount: number;
};

export type AccountsFormPageProps = {
    account: AccountRecord | null;
    accountTypes: AccountTypeOption[];
};
