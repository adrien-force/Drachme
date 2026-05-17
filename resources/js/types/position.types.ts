import type { AccountType } from '@/types/account.types';

export type PositionRecord = {
    id: number;
    account_id: number;
    isin: string;
    label: string;
    quantity: number;
    average_price: number;
    last_price: number | null;
    last_price_at: string | null;
    unit_price: number;
    market_value: number;
    uses_average_price: boolean;
};

export type PositionAccountSummary = {
    id: number;
    name: string;
    type: AccountType;
    currency: string;
};

export type PositionsIndexPageProps = {
    account: PositionAccountSummary;
    positions: PositionRecord[];
    totalValue: number;
    pageDescription: string;
};

export type PortfolioImportHistoryLine = {
    isin: string;
    label: string;
    quantity: number;
    average_price: number;
    last_price: number | null;
    market_value: number;
    quantity_delta: number | null;
    value_delta: number | null;
};

export type PortfolioImportHistoryEntry = {
    id: number;
    imported_at: string;
    label: string;
    total_market_value: number;
    positions_count: number;
    change_amount: number | null;
    change_pct: number | null;
    original_filename: string | null;
    lines: PortfolioImportHistoryLine[];
};

export type InvestmentAccountRow = {
    id: number;
    name: string;
    logo_url: string | null;
    institution: string | null;
    currency: string;
    current_balance: number;
    positions_count: number;
    positions_value: number;
    import_history: PortfolioImportHistoryEntry[];
};

export type InvestmentsIndexPageProps = {
    accounts: InvestmentAccountRow[];
    marketDataConfigured: boolean;
};
