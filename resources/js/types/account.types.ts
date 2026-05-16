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

export type AccountsShowPageProps = {
    account: AccountRecord;
    transactions: [];
};

export type AccountsFormPageProps = {
    account: AccountRecord | null;
    accountTypes: AccountTypeOption[];
};
