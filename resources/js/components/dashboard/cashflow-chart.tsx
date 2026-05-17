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

    return (
        <Card className="animate-in fade-in duration-500 fill-mode-both delay-150">
            <CardHeader>
                <CardTitle>Revenus et dépenses</CardTitle>
                <CardDescription>Cashflow mensuel</CardDescription>
            </CardHeader>
            <CardContent>
                <ChartContainer
                    config={chartConfig}
                    className="aspect-auto h-72 w-full [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted/15"
                >
                    <BarChart
                        data={data}
                        margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
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
                            activeBar={false}
                        />
                        <Bar
                            dataKey="expense"
                            name="expense"
                            fill="var(--color-expense)"
                            radius={[4, 4, 0, 0]}
                            activeBar={false}
                        />
                    </BarChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
