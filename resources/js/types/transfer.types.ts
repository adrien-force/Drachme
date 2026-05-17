import type { TransactionAccountOption } from '@/types/transaction.types';

export type TransferSuggestionTransaction = {
    id: number;
    account_id: number;
    account_name: string;
    account_logo_url: string | null;
    date: string;
    label: string;
    amount: string;
};

export type TransferSuggestionRecord = {
    outgoing: TransferSuggestionTransaction;
    incoming: TransferSuggestionTransaction;
    score: number;
};

export type TransfersIndexPageProps = {
    suggestions: TransferSuggestionRecord[];
    accountOptions: TransactionAccountOption[];
};
