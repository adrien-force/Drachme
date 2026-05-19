import type { CategorySelectOption } from '@/types/category.types';
import type { TransactionsFormPageProps } from '@/types/transaction.types';
import type {
    AccountTransactionItem,
    TransactionType,
    TransactionTypeOption,
} from '@/types/transaction.types';

export type SettlementPeriodMode =
    | 'since_last_settlement'
    | 'calendar_month'
    | 'billing_cycle';

export type AccountType =
    | 'checking'
    | 'savings'
    | 'invest'
    | 'credit'
    | 'credit_card'
    | 'cash';

export type AccountRecord = {
    id: number;
    name: string;
    institution: string | null;
    logo_url: string | null;
    type: AccountType;
    initial_balance: number;
    current_balance: number;
    /** Market value of open positions (invest accounts only). */
    positions_value: number | null;
    /** Positive debt on credit card accounts (detail only, excluded from net worth). */
    amount_owed: number | null;
    current_period_spend: number | null;
    /** Sum of transaction amounts (current_balance − initial_balance). */
    transactions_net?: number;
    currency: string;
    opened_at: string | null;
    is_archived: boolean;
    last_activity_at: string | null;
    settlement_account_id: number | null;
    billing_day: number | null;
    settlement_label_pattern: string | null;
    settlement_period_mode: SettlementPeriodMode;
    settlement_account: { id: number; name: string } | null;
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

export type AccountBalanceHistoryMode = 'balance' | 'amount_owed';

export type AccountBalanceHistory = {
    from: string;
    to: string;
    points: AccountBalancePoint[];
    is_all_time: boolean;
    mode: AccountBalanceHistoryMode;
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

export type CreditCardSettlementPurchase = {
    id: number;
    date: string;
    label: string;
    amount: number;
    category_name: string | null;
    category_color: string | null;
};

export type CreditCardSettlementCandidate = {
    id: number;
    account_id: number;
    date: string;
    amount: number;
    label: string;
};

export type CreditCardSettlement = {
    id: number;
    account_id: number;
    date: string;
    amount: number;
    label: string;
    is_linked: boolean;
    spend_matches_settlement: boolean;
    checking_label: string | null;
    checking_date: string | null;
    period_start: string;
    period_end: string;
    period_start_is_manual: boolean;
    spend_total: number;
    purchase_count: number;
    purchases: CreditCardSettlementPurchase[];
};

export type CreditCardOpenPeriod = {
    period_start: string;
    period_end: string;
    spend_total: number;
    purchase_count: number;
    purchases: CreditCardSettlementPurchase[];
};

export type CreditCardSettlementsData = {
    settlements: CreditCardSettlement[];
    open_period: CreditCardOpenPeriod | null;
    unmarked_candidates: CreditCardSettlementCandidate[];
};

export type AccountsShowPageProps = {
    account: AccountRecord;
    transactions: PaginatedTransactions;
    transactionFilters: AccountTransactionFilters;
    transactionTypeOptions: TransactionTypeOption[];
    categoryOptions: CategorySelectOption[];
    perPageOptions: number[];
    balanceHistory: AccountBalanceHistory | null;
    creditCardSettlements: CreditCardSettlementsData | null;
    transactionEdit: TransactionsFormPageProps | null;
    uncategorizedCount: number;
};

export type SettlementAccountOption = {
    value: number;
    label: string;
};

export type SettlementPeriodModeOption = {
    value: SettlementPeriodMode;
    label: string;
};

export type AccountsFormPageProps = {
    account: AccountRecord | null;
    accountTypes: AccountTypeOption[];
    settlementAccountOptions: SettlementAccountOption[];
    settlementPeriodModeOptions: SettlementPeriodModeOption[];
};
