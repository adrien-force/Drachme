import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { useMemo } from 'react';
import { CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { AccountBalancePoint } from '@/types/account.types';

type AccountBalanceChartProps = {
    points: AccountBalancePoint[];
};

export function AccountBalanceChart({ points }: AccountBalanceChartProps) {
    const { t, locale } = useTranslation();

    const chartConfig = {
        balance: {
            label: t('accounts.current_balance'),
            color: 'var(--chart-net-worth)',
        },
    } satisfies ChartConfig;
    const dateLocale = locale === 'fr' ? fr : enUS;

    const data = useMemo(
        () =>
            points.map((point) => ({
                ...point,
                label: format(parseISO(point.date), 'd MMM', { locale: dateLocale }),
            })),
        [dateLocale, points],
    );

    return (
        <ChartContainer config={chartConfig} className="aspect-auto h-72 w-full">
            <LineChart
                data={data}
                margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
            >
                <CartesianGrid vertical={false} strokeDasharray="4 4" />
                <XAxis
                    dataKey="label"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={32}
                />
                <YAxis
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    width={72}
                    tickFormatter={(value) => formatCurrency(Number(value))}
                />
                <ChartTooltip
                    content={
                        <ChartTooltipContent
                            formatter={(value) =>
                                formatCurrency(Number(value), { precise: true })
                            }
                        />
                    }
                />
                <Line
                    type="monotone"
                    dataKey="balance"
                    stroke="var(--color-balance)"
                    strokeWidth={2}
                    dot={false}
                />
            </LineChart>
        </ChartContainer>
    );
}
