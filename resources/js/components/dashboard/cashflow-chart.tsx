import { router } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, Legend, XAxis, YAxis } from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { useTranslation } from '@/hooks/use-translation';
import { transactionsUrlForCashflowBar } from '@/lib/dashboard-cashflow-link';
import { formatCurrency } from '@/lib/format-currency';
import type { CashflowPoint } from '@/types/dashboard.types';

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
};

export function CashflowChart({ data }: CashflowChartProps) {
    const { t } = useTranslation();

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

        const flow = entry.dataKey === 'income' ? 'credit' : 'debit';

        router.get(transactionsUrlForCashflowBar(entry.payload, flow));
    };

    return (
        <Card className="animate-in fade-in duration-500 fill-mode-both delay-150">
            <CardHeader>
                <CardTitle>{t('dashboard.cashflow_chart_title')}</CardTitle>
                <CardDescription>{t('dashboard.cashflow_chart_description')}</CardDescription>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground mb-3 text-xs">
                    {t('dashboard.cashflow_bar_hint')}
                </p>
                <ChartContainer
                    config={chartConfig}
                    className="aspect-auto h-72 w-full [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted/15"
                >
                    <BarChart
                        data={data}
                        margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
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
            </CardContent>
        </Card>
    );
}
