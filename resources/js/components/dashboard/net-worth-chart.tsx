import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import {
    DASHBOARD_CHART_PLOT_CLASS,
    DashboardChartCard,
} from '@/components/dashboard/dashboard-chart-card';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { NetWorthPoint } from '@/types/dashboard.types';

type NetWorthChartProps = {
    data: NetWorthPoint[];
};

function ChartTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: Array<{ payload: NetWorthPoint }>;
}) {
    if (!active || !payload?.[0]) {
        return null;
    }

    const point = payload[0].payload;

    return (
        <div className="bg-popover text-popover-foreground rounded-lg border px-3 py-2 text-sm shadow-md">
            <p className="text-muted-foreground">{point.label}</p>
            <p className="font-medium">{formatCurrency(point.value)}</p>
        </div>
    );
}

export function NetWorthChart({ data }: NetWorthChartProps) {
    const { t } = useTranslation();

    return (
        <DashboardChartCard
            title={t('dashboard.net_worth_chart_title')}
            description={t('dashboard.net_worth_chart_description')}
            className="animate-in fade-in duration-500 fill-mode-both"
        >
            <div className={DASHBOARD_CHART_PLOT_CLASS}>
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart
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
                        <Tooltip content={<ChartTooltip />} />
                        <Line
                            type="monotone"
                            dataKey="value"
                            stroke="var(--chart-net-worth)"
                            strokeWidth={2}
                            dot={false}
                            activeDot={{ r: 4, fill: 'var(--chart-net-worth)' }}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </DashboardChartCard>
    );
}
