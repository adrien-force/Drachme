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
import type {
    AccountBalanceHistoryMode,
    AccountBalancePoint,
} from '@/types/account.types';

type AccountBalanceChartProps = {
    points: AccountBalancePoint[];
    mode?: AccountBalanceHistoryMode;
};

function resolveChartDateFormats(points: AccountBalancePoint[]): {
    axisFormat: string;
    tooltipFormat: string;
} {
    if (points.length === 0) {
        return { axisFormat: 'd MMM yyyy', tooltipFormat: 'PP' };
    }

    const first = parseISO(points[0]!.date);
    const last = parseISO(points[points.length - 1]!.date);
    const yearSpan = last.getFullYear() - first.getFullYear();

    if (yearSpan >= 8) {
        return { axisFormat: 'yyyy', tooltipFormat: 'PP' };
    }

    if (yearSpan >= 1) {
        return { axisFormat: 'MMM yyyy', tooltipFormat: 'PP' };
    }

    if (
        first.getFullYear() !== last.getFullYear() ||
        first.getMonth() !== last.getMonth()
    ) {
        return { axisFormat: 'd MMM yyyy', tooltipFormat: 'PP' };
    }

    return { axisFormat: 'd MMM', tooltipFormat: 'PP' };
}

export function AccountBalanceChart({ points, mode = 'balance' }: AccountBalanceChartProps) {
    const { t, locale } = useTranslation();

    const chartConfig = {
        balance: {
            label:
                mode === 'amount_owed'
                    ? t('accounts.amount_owed')
                    : t('accounts.current_balance'),
            color: 'var(--chart-net-worth)',
        },
    } satisfies ChartConfig;
    const dateLocale = locale === 'fr' ? fr : enUS;

    const { axisFormat, tooltipFormat } = useMemo(
        () => resolveChartDateFormats(points),
        [points],
    );

    const formatChartDate = useMemo(
        () => (date: string, pattern: string) =>
            format(parseISO(date), pattern, { locale: dateLocale }),
        [dateLocale],
    );

    return (
        <ChartContainer config={chartConfig} className="aspect-auto h-72 w-full">
            <LineChart
                data={points}
                margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
            >
                <CartesianGrid vertical={false} strokeDasharray="4 4" />
                <XAxis
                    dataKey="date"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={32}
                    tickFormatter={(value) =>
                        formatChartDate(String(value), axisFormat)
                    }
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
                            labelFormatter={(_, payload) => {
                                const date = payload?.[0]?.payload?.date;

                                return typeof date === 'string'
                                    ? formatChartDate(date, tooltipFormat)
                                    : '';
                            }}
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
