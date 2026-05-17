import { Cell, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import { DashboardChartCard } from '@/components/dashboard/dashboard-chart-card';
import { GlassPanel } from '@/components/glass-panel';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import { cn } from '@/lib/utils';
import type { RecurringSummary } from '@/types/recurring.types';

const topChartConfig = {
    monthly: { label: 'Mensuel', color: 'var(--primary)' },
} satisfies ChartConfig;

const LABEL_MAX_LENGTH = 26;

function truncateAxisLabel(value: string): string {
    return value.length > LABEL_MAX_LENGTH
        ? `${value.slice(0, LABEL_MAX_LENGTH - 1)}…`
        : value;
}

type RecurringSummaryChartsProps = {
    summary: RecurringSummary;
};

export function RecurringSummaryCharts({ summary }: RecurringSummaryChartsProps) {
    const { t } = useTranslation();

    const topData = summary.top_items.map((item) => ({
        fullLabel: item.label,
        monthly: Number.parseFloat(item.monthly_amount),
        fill:
            item.transaction_type === 'income'
                ? 'var(--chart-income)'
                : 'var(--chart-expense)',
    }));

    const chartHeight = Math.min(280, Math.max(140, topData.length * 36 + 24));

    return (
        <div className="grid gap-4 lg:grid-cols-[minmax(12rem,16rem)_minmax(0,1fr)] lg:items-stretch">
            <GlassPanel className="flex flex-col justify-center space-y-1 p-4 md:p-6">
                <p className="text-muted-foreground text-sm">{t('recurring.monthly_expense')}</p>
                <p className="text-chart-expense text-2xl font-semibold tabular-nums">
                    {formatCurrency(Number.parseFloat(summary.monthly_expense), {
                        precise: true,
                    })}
                </p>
                <p className="text-muted-foreground text-xs leading-relaxed">
                    {t('recurring.per_month')} · {t('recurring.monthly_equiv_hint')} ·{' '}
                    {t('recurring.confirmed_count', { count: summary.confirmed_count })}
                </p>
            </GlassPanel>

            {topData.length > 0 ? (
                <DashboardChartCard
                    title={t('recurring.top_chart_title')}
                    className="h-full min-h-0"
                >
                    <ChartContainer
                        config={topChartConfig}
                        className={cn('aspect-auto w-full')}
                        style={{ height: chartHeight }}
                    >
                        <BarChart
                            data={topData}
                            layout="vertical"
                            margin={{ top: 4, right: 12, left: 4, bottom: 4 }}
                        >
                            <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                            <XAxis
                                type="number"
                                tickLine={false}
                                axisLine={false}
                                tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                                tickFormatter={(value) => formatCurrency(Number(value))}
                            />
                            <YAxis
                                type="category"
                                dataKey="fullLabel"
                                width={148}
                                tickLine={false}
                                axisLine={false}
                                tick={{ fill: 'var(--muted-foreground)', fontSize: 11 }}
                                tickFormatter={truncateAxisLabel}
                            />
                            <ChartTooltip
                                content={
                                    <ChartTooltipContent
                                        labelKey="fullLabel"
                                        formatter={(value) =>
                                            formatCurrency(Number(value), { precise: true })
                                        }
                                    />
                                }
                            />
                            <Bar dataKey="monthly" radius={4} barSize={22}>
                                {topData.map((entry, index) => (
                                    <Cell key={`${entry.fullLabel}-${index}`} fill={entry.fill} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ChartContainer>
                </DashboardChartCard>
            ) : (
                <GlassPanel className="text-muted-foreground flex items-center justify-center p-6 text-sm">
                    {t('recurring.confirmed_empty')}
                </GlassPanel>
            )}
        </div>
    );
}
