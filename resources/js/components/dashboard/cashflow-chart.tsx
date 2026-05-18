import { router } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, Legend, XAxis, YAxis } from 'recharts';

import {
    DASHBOARD_CHART_PLOT_CLASS,
    DashboardChartCard,
} from '@/components/dashboard/dashboard-chart-card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { enUS, fr } from 'date-fns/locale';

import { useTranslation } from '@/hooks/use-translation';
import { formatDashboardDateRangeLabel } from '@/lib/dashboard-date-range-label';
import { transactionsUrlForCashflowBar } from '@/lib/dashboard-cashflow-link';
import { formatCurrency } from '@/lib/format-currency';
import type { CashflowPoint, DashboardDateRange } from '@/types/dashboard.types';

const chartConfig = {
    income: {
        label: 'Revenus',
        color: 'var(--chart-income)',
    },
    expense: {
        label: 'Dépenses',
        color: 'var(--chart-expense)',
    },
} satisfies ChartConfig;

const tooltipCursor = {
    fill: 'var(--muted)',
    fillOpacity: 0.18,
} as const;

type CashflowChartProps = {
    data: CashflowPoint[];
    dateRange: DashboardDateRange;
};

export function CashflowChart({ data, dateRange }: CashflowChartProps) {
    const { t, locale } = useTranslation();
    const dateLocale = locale === 'fr' ? fr : enUS;
    const rangeLabel = formatDashboardDateRangeLabel(dateRange, t, dateLocale);

    const handleBarClick = (
        state: {
            activePayload?: Array<{
                dataKey?: string | number;
                payload?: CashflowPoint;
            }>;
        } | null,
    ) => {
        const entry = state?.activePayload?.[0];

        if (!entry?.payload?.period_start || !entry.payload.period_end) {
            return;
        }

        router.get(transactionsUrlForCashflowBar(entry.payload));
    };

    return (
        <DashboardChartCard
            title={t('dashboard.cashflow_chart_title')}
            description={rangeLabel}
            className="animate-in fade-in duration-500 fill-mode-both delay-150"
        >
            <ChartContainer
                config={chartConfig}
                className={`${DASHBOARD_CHART_PLOT_CLASS} aspect-auto [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted/15`}
            >
                <BarChart
                    data={data}
                    margin={{ top: 8, right: 8, left: 0, bottom: 24 }}
                    onClick={handleBarClick}
                    style={{ cursor: 'pointer' }}
                >
                    <CartesianGrid
                        stroke="var(--border)"
                        strokeDasharray="4 4"
                        vertical={false}
                    />
                    <XAxis
                        dataKey="label"
                        tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                        tickLine={false}
                        axisLine={false}
                        interval="preserveStartEnd"
                    />
                    <YAxis
                        tickFormatter={(v) => formatCurrency(Number(v))}
                        tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                        tickLine={false}
                        axisLine={false}
                        width={72}
                    />
                    <ChartTooltip
                        cursor={tooltipCursor}
                        content={
                            <ChartTooltipContent
                                formatter={(value) =>
                                    formatCurrency(Number(value), { precise: true })
                                }
                            />
                        }
                    />
                    <Legend
                        formatter={(value) =>
                            value === 'income'
                                ? t('dashboard.cashflow_income')
                                : t('dashboard.cashflow_expense')
                        }
                    />
                    <Bar
                        dataKey="income"
                        name="income"
                        fill="var(--color-income)"
                        radius={[4, 4, 0, 0]}
                    />
                    <Bar
                        dataKey="expense"
                        name="expense"
                        fill="var(--color-expense)"
                        radius={[4, 4, 0, 0]}
                    />
                </BarChart>
            </ChartContainer>
        </DashboardChartCard>
    );
}
