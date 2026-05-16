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
};

export type AccountTypeOption = {
    value: AccountType;
    label: string;
};

export type AccountsIndexPageProps = {
    accounts: AccountRecord[];
};

export type AccountsFormPageProps = {
    account: AccountRecord | null;
    accountTypes: AccountTypeOption[];
};
