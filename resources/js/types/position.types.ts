import type { AccountType } from '@/types/account.types';

export type PositionRecord = {
    id: number;
    account_id: number;
    isin: string;
    market_symbol: string | null;
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
    portfolioValueSeries: PositionPortfolioValuePoint[];
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

export type PositionInferredMovement = {
    snapshot_id: number;
    imported_at: string;
    side: 'buy' | 'sell';
    quantity: number;
    quantity_before: number;
    quantity_after: number;
    unit_price: number | null;
    inferred: true;
};

export type PositionSnapshotPricePoint = {
    date: string;
    price: number;
};

export type PositionPortfolioValuePoint = {
    date: string;
    value: number;
};

export type PositionsShowPageProps = {
    position: PositionRecord;
    account: Pick<PositionAccountSummary, 'id' | 'name' | 'currency'>;
    inferredMovements: PositionInferredMovement[];
    portfolioValueSeries: PositionPortfolioValuePoint[];
    marketPriceSeries: PositionSnapshotPricePoint[];
    marketDataConfigured: boolean;
};
