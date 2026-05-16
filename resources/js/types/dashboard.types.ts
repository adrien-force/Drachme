export type DashboardKpis = {
    net_worth: number;
    net_worth_change_pct: number;
    monthly_cashflow: number;
};

export type NetWorthPoint = {
    month: string;
    label: string;
    value: number;
};

export type CashflowPoint = {
    month: string;
    label: string;
    income: number;
    expense: number;
};

export type DashboardPageProps = {
    kpis: DashboardKpis;
    netWorthHistory: NetWorthPoint[];
    cashflow: CashflowPoint[];
    isDemoData: boolean;
};
