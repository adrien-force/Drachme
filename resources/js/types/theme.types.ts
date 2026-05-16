export type ThemeColorKey =
    | 'primary'
    | 'chart_income'
    | 'chart_expense'
    | 'chart_net_worth'
    | 'chart_secondary';

export type ThemeColorMap = Record<ThemeColorKey, string>;

export type ThemeSharedProps = {
    colors: ThemeColorMap;
    defaults: ThemeColorMap;
};
