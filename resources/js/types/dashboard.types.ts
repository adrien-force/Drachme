export type DashboardKpis = {
    net_worth: number;
    net_worth_change_pct: number;
    monthly_cashflow: number;
    portfolio_value: number;
    portfolio_change_pct: number;
    total_assets: number;
};

export type AccountAllocationSlice = {
    type: string;
    label: string;
    value: number;
};

export type NetWorthPoint = {
    month: string;
    label: string;
    value: number;
};

export type PortfolioEvolutionPoint = {
    key: string;
    label: string;
    value: number;
    imported_at: string;
    account_id: number;
    account_name: string;
    original_filename: string | null;
};

export type CashflowPoint = {
    month: string;
    label: string;
    income: number;
    expense: number;
    period_start: string;
    period_end: string;
};

export type DashboardDateRange = {
    preset: '3m' | '6m' | '12m' | 'ytd' | 'all' | 'custom';
    from: string;
    to: string;
};

export type DashboardPageProps = {
    dateRange: DashboardDateRange;
    kpis: DashboardKpis;
    netWorthHistory: NetWorthPoint[];
    portfolioHistory: PortfolioEvolutionPoint[];
    accountAllocation: AccountAllocationSlice[];
    cashflow: CashflowPoint[];
    isDemoData: boolean;
};
