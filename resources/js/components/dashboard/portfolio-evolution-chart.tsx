import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { PortfolioEvolutionPoint } from '@/types/dashboard.types';

type PortfolioEvolutionChartProps = {
    data: PortfolioEvolutionPoint[];
};

function ChartTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: Array<{ payload: PortfolioEvolutionPoint }>;
}) {
    if (!active || !payload?.[0]) {
        return null;
    }

    const point = payload[0].payload;

    return (
        <div className="bg-popover text-popover-foreground max-w-xs rounded-lg border px-3 py-2 text-sm shadow-md">
            <p className="text-muted-foreground">{point.label}</p>
            <p className="font-medium">{formatCurrency(point.value)}</p>
            {point.account_name ? (
                <p className="text-muted-foreground mt-1 text-xs">{point.account_name}</p>
            ) : null}
            {point.original_filename ? (
                <p className="text-muted-foreground truncate text-xs" title={point.original_filename}>
                    {point.original_filename}
                </p>
            ) : null}
        </div>
    );
}

export function PortfolioEvolutionChart({ data }: PortfolioEvolutionChartProps) {
    const { t } = useTranslation();

    if (data.length === 0) {
        return null;
    }

    return (
        <Card className="animate-in fade-in duration-500 fill-mode-both">
            <CardHeader>
                <CardTitle>{t('dashboard.portfolio_evolution')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="h-72 w-full">
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
                                tick={{ fill: 'var(--muted-foreground)', fontSize: 10 }}
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
                                stroke="var(--chart-income)"
                                strokeWidth={2}
                                dot={{ r: 3, fill: 'var(--chart-income)' }}
                                activeDot={{ r: 5, fill: 'var(--chart-income)' }}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
