import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { useMemo } from 'react';
import {
    CartesianGrid,
    Line,
    LineChart,
    ReferenceDot,
    XAxis,
    YAxis,
} from 'recharts';

import { DashboardChartCard } from '@/components/dashboard/dashboard-chart-card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { PositionInferredMovement } from '@/types/position.types';

type ChartPoint = {
    date: string;
    label: string;
    value: number;
};

type PositionPriceChartProps = {
    title: string;
    description: string;
    /** ISO date + numeric value (unit price or portfolio value). */
    series: ReadonlyArray<{ date: string; value: number }>;
    valueLabel: string;
    movements?: PositionInferredMovement[];
    /** Buy/sell markers on the line (portfolio chart). */
    showMovementMarkers?: boolean;
    /** Dots on the line vertices (off for market history). */
    showLineDots?: boolean;
};

export function PositionPriceChart({
    title,
    description,
    series,
    valueLabel,
    movements = [],
    showMovementMarkers = false,
    showLineDots = false,
}: PositionPriceChartProps) {
    const { locale } = useTranslation();
    const dateLocale = locale === 'fr' ? fr : enUS;

    const chartConfig = {
        value: {
            label: valueLabel,
            color: 'var(--chart-net-worth)',
        },
    } satisfies ChartConfig;

    const data = useMemo<ChartPoint[]>(
        () =>
            series.map((point) => ({
                date: point.date,
                value: point.value,
                label: format(parseISO(point.date), 'd MMM yy', { locale: dateLocale }),
            })),
        [dateLocale, series],
    );

    const markers = useMemo(() => {
        if (!showMovementMarkers) {
            return [];
        }

        return movements
            .map((movement) => {
                const date = movement.imported_at.slice(0, 10);
                const row = data.find((point) => point.date === date);

                if (row === undefined) {
                    return null;
                }

                return {
                    key: `${movement.snapshot_id}-${movement.side}`,
                    chartLabel: row.label,
                    value: row.value,
                    side: movement.side,
                };
            })
            .filter((marker): marker is NonNullable<typeof marker> => marker !== null);
    }, [data, movements, showMovementMarkers]);

    if (data.length === 0) {
        return null;
    }

    return (
        <DashboardChartCard title={title} description={description}>
            <ChartContainer config={chartConfig} className="aspect-auto h-72 w-full">
                <LineChart data={data} margin={{ top: 12, right: 12, left: 0, bottom: 0 }}>
                    <CartesianGrid vertical={false} strokeDasharray="4 4" />
                    <XAxis
                        dataKey="label"
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        minTickGap={24}
                    />
                    <YAxis
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        width={80}
                        tickFormatter={(tickValue) => formatCurrency(Number(tickValue))}
                    />
                    <ChartTooltip
                        content={
                            <ChartTooltipContent
                                formatter={(tickValue) =>
                                    formatCurrency(Number(tickValue), { precise: true })
                                }
                            />
                        }
                    />
                    <Line
                        type="monotone"
                        dataKey="value"
                        stroke="var(--color-value)"
                        strokeWidth={2}
                        dot={showLineDots ? { r: 3, fill: 'var(--color-value)' } : false}
                        activeDot={showLineDots ? { r: 4 } : { r: 4, fill: 'var(--color-value)' }}
                    />
                    {markers.map((marker) => (
                        <ReferenceDot
                            key={marker.key}
                            x={marker.chartLabel}
                            y={marker.value}
                            r={6}
                            fill={
                                marker.side === 'buy'
                                    ? 'var(--chart-income)'
                                    : 'var(--chart-expense)'
                            }
                            stroke="var(--background)"
                            strokeWidth={2}
                        />
                    ))}
                </LineChart>
            </ChartContainer>
        </DashboardChartCard>
    );
}
