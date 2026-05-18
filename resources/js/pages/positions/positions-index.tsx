import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, LineChart, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { PositionPriceChart } from '@/components/positions/position-price-chart';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import { show as showPosition } from '@/routes/positions';
import type { PositionRecord, PositionsIndexPageProps } from '@/types/position.types';

type FormMode =
    | { type: 'create' }
    | { type: 'edit'; position: PositionRecord };

export default function PositionsIndex({
    account,
    positions,
    totalValue,
    portfolioValueSeries,
    pageDescription,
}: PositionsIndexPageProps) {
    const { t } = useTranslation();
    const [formMode, setFormMode] = useState<FormMode | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<PositionRecord | null>(null);

    const form = useForm({
        isin: '',
        market_symbol: '',
        label: '',
        quantity: '',
        average_price: '',
        last_price: '',
    });

    const openCreate = () => {
        form.clearErrors();
        form.setData({
            isin: '',
            market_symbol: '',
            label: '',
            quantity: '',
            average_price: '',
            last_price: '',
        });
        setFormMode({ type: 'create' });
    };

    const openEdit = (position: PositionRecord) => {
        form.clearErrors();
        form.setData({
            isin: position.isin,
            market_symbol: position.market_symbol ?? '',
            label: position.label,
            quantity: String(position.quantity),
            average_price: String(position.average_price),
            last_price:
                position.last_price !== null ? String(position.last_price) : '',
        });
        setFormMode({ type: 'edit', position });
    };

    const submitForm = () => {
        const payload = {
            isin: form.data.isin.trim().toUpperCase(),
            market_symbol: form.data.market_symbol.trim() === ''
                ? null
                : form.data.market_symbol.trim().toUpperCase(),
            label: form.data.label,
            quantity: form.data.quantity,
            average_price: form.data.average_price,
            last_price: form.data.last_price === '' ? null : form.data.last_price,
        };

        if (formMode?.type === 'edit') {
            form.transform(() => payload);
            form.put(
                `/accounts/${account.id}/positions/${formMode.position.id}`,
                {
                    preserveScroll: true,
                    onSuccess: () => setFormMode(null),
                },
            );

            return;
        }

        form.transform(() => payload);
        form.post(`/accounts/${account.id}/positions`, {
            preserveScroll: true,
            onSuccess: () => setFormMode(null),
        });
    };

    const submitDelete = () => {
        if (!deleteTarget) {
            return;
        }

        router.delete(
            `/accounts/${account.id}/positions/${deleteTarget.id}`,
            {
                preserveScroll: true,
                onSuccess: () => setDeleteTarget(null),
            },
        );
    };

    return (
        <>
            <Head title={`${t('positions.title')} — ${account.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Button asChild variant="ghost" size="sm" className="w-fit px-2">
                    <Link href="/investments">
                        <ArrowLeft className="mr-2 size-4" />
                        {t('positions.back_to_portfolio')}
                    </Link>
                </Button>

                <FadeIn>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {account.name}
                            </h1>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {pageDescription}
                            </p>
                            <p className="mt-2 text-sm">
                                <span className="text-muted-foreground">
                                    {t('positions.total_value')}:{' '}
                                </span>
                                <span className="font-semibold tabular-nums">
                                    {formatCurrency(totalValue, { precise: true })}
                                </span>
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href={`/accounts/${account.id}`}>
                                    {t('positions.back_to_account')}
                                </Link>
                            </Button>
                            <Button type="button" size="sm" onClick={openCreate}>
                                <Plus className="mr-2 size-4" />
                                {t('positions.add')}
                            </Button>
                        </div>
                    </div>
                </FadeIn>

                {portfolioValueSeries.length > 0 ? (
                    <FadeIn delay={0.04}>
                        <PositionPriceChart
                            title={t('positions.account_portfolio_value_chart_title')}
                            description={t('positions.account_portfolio_value_chart_hint')}
                            series={portfolioValueSeries}
                            valueLabel={t('positions.total_value')}
                        />
                    </FadeIn>
                ) : null}

                {positions.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('positions.empty')}
                            </p>
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <FadeIn>
                        <GlassPanel className="divide-y divide-border/60 p-0">
                            {positions.map((position) => (
                                <div
                                    key={position.id}
                                    className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0 space-y-1">
                                        <p className="font-medium">{position.label}</p>
                                        <p className="text-muted-foreground font-mono text-xs">
                                            {position.isin}
                                            {position.market_symbol ? (
                                                <>
                                                    {' · '}
                                                    {position.market_symbol}
                                                </>
                                            ) : null}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {t('positions.quantity')}:{' '}
                                            <span className="tabular-nums">
                                                {position.quantity}
                                            </span>
                                        </p>
                                    </div>
                                    <div className="flex flex-col items-start gap-2 sm:items-end">
                                        <div className="flex items-center gap-2">
                                            <span className="text-muted-foreground text-xs">
                                                {t('positions.unit_price')}
                                            </span>
                                            <span className="tabular-nums">
                                                {formatCurrency(position.unit_price, {
                                                    precise: true,
                                                })}
                                            </span>
                                            {position.uses_average_price && (
                                                <Badge variant="secondary">
                                                    {t('positions.pru_badge')}
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="font-semibold tabular-nums">
                                            {formatCurrency(position.market_value, {
                                                precise: true,
                                            })}
                                        </p>
                                        <div className="flex gap-1">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={showPosition.url(position.id)}
                                                    title={t('positions.open_detail')}
                                                >
                                                    <LineChart className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => openEdit(position)}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => setDeleteTarget(position)}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </GlassPanel>
                    </FadeIn>
                )}
            </div>

            <Dialog open={formMode !== null} onOpenChange={(open) => !open && setFormMode(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {formMode?.type === 'edit'
                                ? t('positions.edit')
                                : t('positions.add')}
                        </DialogTitle>
                        <DialogDescription>{t('positions.isin_hint')}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="position-isin">{t('positions.isin')}</Label>
                            <Input
                                id="position-isin"
                                value={form.data.isin}
                                maxLength={12}
                                className="font-mono uppercase"
                                onChange={(event) =>
                                    form.setData('isin', event.target.value.toUpperCase())
                                }
                            />
                            <InputError message={form.errors.isin} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="position-market-symbol">
                                {t('positions.market_symbol')}
                            </Label>
                            <Input
                                id="position-market-symbol"
                                value={form.data.market_symbol}
                                placeholder={t('positions.market_symbol_placeholder')}
                                className="font-mono uppercase"
                                onChange={(event) =>
                                    form.setData(
                                        'market_symbol',
                                        event.target.value.toUpperCase(),
                                    )
                                }
                            />
                            <p className="text-muted-foreground text-xs">
                                {t('positions.market_symbol_hint')}
                            </p>
                            <InputError message={form.errors.market_symbol} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="position-label">{t('positions.label')}</Label>
                            <Input
                                id="position-label"
                                value={form.data.label}
                                placeholder={t('positions.label_placeholder')}
                                onChange={(event) =>
                                    form.setData('label', event.target.value)
                                }
                            />
                            <InputError message={form.errors.label} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="position-quantity">
                                    {t('positions.quantity')}
                                </Label>
                                <Input
                                    id="position-quantity"
                                    type="number"
                                    min="0"
                                    step="any"
                                    value={form.data.quantity}
                                    onChange={(event) =>
                                        form.setData('quantity', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.quantity} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="position-average-price">
                                    {t('positions.average_price')}
                                </Label>
                                <Input
                                    id="position-average-price"
                                    type="number"
                                    min="0"
                                    step="any"
                                    value={form.data.average_price}
                                    onChange={(event) =>
                                        form.setData('average_price', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.average_price} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="position-last-price">
                                {t('positions.last_price')}
                            </Label>
                            <Input
                                id="position-last-price"
                                type="number"
                                min="0"
                                step="any"
                                value={form.data.last_price}
                                onChange={(event) =>
                                    form.setData('last_price', event.target.value)
                                }
                            />
                            <InputError message={form.errors.last_price} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setFormMode(null)}
                        >
                            {t('positions.cancel')}
                        </Button>
                        <Button type="button" onClick={submitForm} disabled={form.processing}>
                            {t('positions.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleteTarget !== null}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('positions.delete')}</DialogTitle>
                        <DialogDescription>
                            {deleteTarget
                                ? t('positions.delete_confirm', {
                                      label: deleteTarget.label,
                                  })
                                : ''}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleteTarget(null)}
                        >
                            {t('positions.cancel')}
                        </Button>
                        <Button type="button" variant="destructive" onClick={submitDelete}>
                            {t('positions.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
