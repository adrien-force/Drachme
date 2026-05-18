import { Head, Link, router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { ArrowLeft, RefreshCw, TrendingUp } from 'lucide-react';
import { useState } from 'react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { PositionPriceChart } from '@/components/positions/position-price-chart';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import {
    index as positionsIndex,
    refreshHistory as refreshHistoryRoute,
    refreshPrice as refreshPriceRoute,
} from '@/routes/positions';
import type { PositionsShowPageProps } from '@/types/position.types';

export default function PositionsShow({
    position,
    account,
    inferredMovements,
    portfolioValueSeries,
    marketPriceSeries,
    marketDataConfigured,
}: PositionsShowPageProps) {
    const { t, locale } = useTranslation();
    const dateLocale = locale === 'fr' ? fr : enUS;
    const [refreshingPrice, setRefreshingPrice] = useState(false);
    const [refreshingHistory, setRefreshingHistory] = useState(false);

    return (
        <>
            <Head title={`${position.label} — ${t('positions.show_title')}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <FadeIn>
                    <div className="flex flex-col gap-4">
                        <Button asChild variant="ghost" size="sm" className="w-fit">
                            <Link href={positionsIndex.url(account.id)}>
                                <ArrowLeft className="mr-2 size-4" />
                                {t('positions.back_to_account')}
                            </Link>
                        </Button>

                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex items-start gap-3">
                                <div className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-lg">
                                    <TrendingUp className="size-5" />
                                </div>
                                <div>
                                    <h1 className="text-2xl font-semibold tracking-tight">
                                        {position.label}
                                    </h1>
                                    <p className="text-muted-foreground mt-1 font-mono text-sm">
                                        {position.isin}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {account.name}
                                        {position.market_symbol ? (
                                            <>
                                                {' · '}
                                                {t('positions.market_symbol')}:{' '}
                                                <span className="font-mono">
                                                    {position.market_symbol}
                                                </span>
                                            </>
                                        ) : null}
                                    </p>
                                </div>
                            </div>

                            <GlassPanel className="grid gap-2 p-4 text-sm sm:min-w-56">
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        {t('positions.quantity')}
                                    </span>
                                    <span className="font-medium tabular-nums">
                                        {position.quantity}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        {t('positions.average_price')}
                                    </span>
                                    <span className="tabular-nums">
                                        {formatCurrency(position.average_price, { precise: true })}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">
                                        {t('positions.unit_price')}
                                    </span>
                                    <span className="font-medium tabular-nums">
                                        {formatCurrency(position.unit_price, { precise: true })}
                                        {position.uses_average_price ? (
                                            <Badge variant="secondary" className="ml-2">
                                                {t('positions.pru_badge')}
                                            </Badge>
                                        ) : null}
                                    </span>
                                </div>
                                <div className="flex justify-between gap-4 border-t border-border/60 pt-2">
                                    <span className="text-muted-foreground">
                                        {t('positions.market_value')}
                                    </span>
                                    <span className="font-semibold tabular-nums">
                                        {formatCurrency(position.market_value, { precise: true })}
                                    </span>
                                </div>
                            </GlassPanel>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={!marketDataConfigured || refreshingPrice}
                                title={
                                    marketDataConfigured
                                        ? undefined
                                        : t('investments.market_data_not_configured')
                                }
                                onClick={() => {
                                    setRefreshingPrice(true);
                                    router.post(
                                        refreshPriceRoute.url(position.id),
                                        {},
                                        {
                                            preserveScroll: true,
                                            onFinish: () => setRefreshingPrice(false),
                                        },
                                    );
                                }}
                            >
                                <RefreshCw
                                    className={`mr-2 size-4 ${refreshingPrice ? 'animate-spin' : ''}`}
                                />
                                {t('positions.refresh_price')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={!marketDataConfigured || refreshingHistory}
                                title={
                                    marketDataConfigured
                                        ? undefined
                                        : t('investments.market_data_not_configured')
                                }
                                onClick={() => {
                                    setRefreshingHistory(true);
                                    router.post(
                                        refreshHistoryRoute.url(position.id),
                                        {},
                                        {
                                            preserveScroll: true,
                                            onFinish: () => setRefreshingHistory(false),
                                        },
                                    );
                                }}
                            >
                                <RefreshCw
                                    className={`mr-2 size-4 ${refreshingHistory ? 'animate-spin' : ''}`}
                                />
                                {t('positions.refresh_history')}
                            </Button>
                        </div>
                    </div>
                </FadeIn>

                {portfolioValueSeries.length > 0 ? (
                    <FadeIn delay={0.05}>
                        <PositionPriceChart
                            title={t('positions.portfolio_value_chart_title')}
                            description={t('positions.portfolio_value_chart_hint')}
                            series={portfolioValueSeries}
                            valueLabel={t('positions.market_value')}
                            movements={inferredMovements}
                            showMovementMarkers
                        />
                    </FadeIn>
                ) : null}

                <FadeIn delay={0.08}>
                    {marketPriceSeries.length > 0 ? (
                        <PositionPriceChart
                            title={t('positions.market_price_chart_title')}
                            description={t('positions.market_price_chart_hint')}
                            series={marketPriceSeries.map((point) => ({
                                date: point.date,
                                value: point.price,
                            }))}
                            valueLabel={t('positions.last_price')}
                            showLineDots={false}
                        />
                    ) : (
                        <GlassPanel className="p-6 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('positions.market_price_chart_empty')}
                            </p>
                        </GlassPanel>
                    )}
                </FadeIn>

                <FadeIn delay={0.1}>
                    <GlassPanel className="overflow-hidden">
                        <div className="border-b border-border/60 px-4 py-3 md:px-5">
                            <h2 className="font-medium">
                                {t('positions.inferred_movements_title')}
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {t('positions.inferred_movements_hint')}
                            </p>
                        </div>

                        {inferredMovements.length === 0 ? (
                            <p className="text-muted-foreground px-4 py-8 text-center text-sm md:px-5">
                                {t('positions.inferred_movements_empty')}
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border/60 text-left">
                                            <th className="px-4 py-2 font-medium md:px-5">
                                                {t('positions.movement_date')}
                                            </th>
                                            <th className="px-4 py-2 font-medium md:px-5">
                                                {t('positions.movement_type')}
                                            </th>
                                            <th className="px-4 py-2 text-right font-medium md:px-5">
                                                {t('positions.movement_quantity')}
                                            </th>
                                            <th className="px-4 py-2 text-right font-medium md:px-5">
                                                {t('positions.unit_price')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {inferredMovements.map((movement) => (
                                            <tr
                                                key={`${movement.snapshot_id}-${movement.side}-${movement.imported_at}`}
                                                className="border-b border-border/40 last:border-0"
                                            >
                                                <td className="text-muted-foreground px-4 py-2 tabular-nums md:px-5">
                                                    {format(
                                                        parseISO(movement.imported_at),
                                                        'Pp',
                                                        { locale: dateLocale },
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 md:px-5">
                                                    <Badge
                                                        variant={
                                                            movement.side === 'buy'
                                                                ? 'default'
                                                                : 'destructive'
                                                        }
                                                    >
                                                        {movement.side === 'buy'
                                                            ? t('positions.movement_buy')
                                                            : t('positions.movement_sell')}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-2 text-right tabular-nums md:px-5">
                                                    {movement.quantity}
                                                </td>
                                                <td className="px-4 py-2 text-right tabular-nums md:px-5">
                                                    {movement.unit_price !== null
                                                        ? formatCurrency(movement.unit_price, {
                                                              precise: true,
                                                          })
                                                        : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}
