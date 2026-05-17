import { router } from '@inertiajs/react';
import { ChevronDown, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

import { PortfolioEvolutionChart } from '@/components/dashboard/portfolio-evolution-chart';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { formatCurrency, formatPercent } from '@/lib/format-currency';
import type {
    PortfolioImportHistoryEntry,
    PortfolioImportHistoryLine,
} from '@/types/position.types';
import type { PortfolioEvolutionPoint } from '@/types/dashboard.types';

type PortfolioImportHistoryProps = {
    accountName: string;
    entries: PortfolioImportHistoryEntry[];
};

function formatSignedCurrency(value: number, precise = true): string {
    const sign = value > 0 ? '+' : '';

    return `${sign}${formatCurrency(value, { precise })}`;
}

function formatSignedNumber(value: number): string {
    const sign = value > 0 ? '+' : '';

    return `${sign}${value.toLocaleString('fr-FR', { maximumFractionDigits: 4 })}`;
}

function DeltaCell({ value, format }: { value: number | null; format: 'currency' | 'number' }) {
    if (value === null || value === 0) {
        return <span className="text-muted-foreground">—</span>;
    }

    const formatted =
        format === 'currency' ? formatSignedCurrency(value) : formatSignedNumber(value);

    return (
        <span
            className={cn(
                'tabular-nums',
                value > 0 ? 'text-primary' : 'text-destructive',
            )}
        >
            {formatted}
        </span>
    );
}

function ImportLinesTable({ lines }: { lines: PortfolioImportHistoryLine[] }) {
    const { t } = useTranslation();

    if (lines.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                {t('investments.import_history_no_lines')}
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr className="bg-muted/40 border-b text-left text-xs uppercase">
                        <th className="px-3 py-2">{t('positions.isin')}</th>
                        <th className="px-3 py-2">{t('positions.label')}</th>
                        <th className="px-3 py-2 text-right">{t('positions.quantity')}</th>
                        <th className="px-3 py-2 text-right">{t('positions.average_price')}</th>
                        <th className="px-3 py-2 text-right">
                            {t('investments.import_history_last_price')}
                        </th>
                        <th className="px-3 py-2 text-right">
                            {t('investments.import_history_value')}
                        </th>
                        <th className="px-3 py-2 text-right">
                            {t('investments.import_history_delta_qty')}
                        </th>
                        <th className="px-3 py-2 text-right">
                            {t('investments.import_history_delta_value')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {lines.map((line) => (
                        <tr
                            key={line.isin}
                            className="border-border/50 border-b last:border-0"
                        >
                            <td className="px-3 py-2 font-mono text-xs uppercase">
                                {line.isin}
                            </td>
                            <td className="max-w-[180px] truncate px-3 py-2" title={line.label}>
                                {line.label}
                            </td>
                            <td className="px-3 py-2 text-right tabular-nums">
                                {line.quantity}
                            </td>
                            <td className="px-3 py-2 text-right tabular-nums">
                                {formatCurrency(line.average_price, { precise: true })}
                            </td>
                            <td className="px-3 py-2 text-right tabular-nums">
                                {line.last_price != null
                                    ? formatCurrency(line.last_price, { precise: true })
                                    : '—'}
                            </td>
                            <td className="px-3 py-2 text-right tabular-nums">
                                {formatCurrency(line.market_value, { precise: true })}
                            </td>
                            <td className="px-3 py-2 text-right">
                                <DeltaCell value={line.quantity_delta} format="number" />
                            </td>
                            <td className="px-3 py-2 text-right">
                                <DeltaCell value={line.value_delta} format="currency" />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function PortfolioImportHistory({
    accountName,
    entries,
}: PortfolioImportHistoryProps) {
    const { t } = useTranslation();
    const [deleteTarget, setDeleteTarget] = useState<PortfolioImportHistoryEntry | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);

    const isLatestEntry = entries.length > 0 && deleteTarget?.id === entries[0]?.id;

    const submitDelete = () => {
        if (deleteTarget === null) {
            return;
        }

        setDeleting(true);
        router.delete(`/investments/snapshots/${deleteTarget.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    const chartPoints = useMemo((): PortfolioEvolutionPoint[] => {
        return [...entries]
            .reverse()
            .map((entry) => ({
                key: String(entry.id),
                label: entry.label,
                value: entry.total_market_value,
                imported_at: entry.imported_at,
                account_id: 0,
                account_name: accountName,
                original_filename: entry.original_filename,
            }));
    }, [accountName, entries]);

    if (entries.length === 0) {
        return null;
    }

    return (
        <div className="mt-4 space-y-4 border-t border-white/10 pt-4">
            <div>
                <h3 className="text-sm font-semibold">
                    {t('investments.import_history_title')}
                </h3>
                <p className="text-muted-foreground text-xs">
                    {t('investments.import_history_count', { count: entries.length })}
                </p>
            </div>

            {entries.length >= 2 ? (
                <PortfolioEvolutionChart data={chartPoints} />
            ) : null}

            <div className="space-y-2">
                {entries.map((entry) => (
                    <Collapsible key={entry.id} className="rounded-lg border border-white/10">
                        <CollapsibleTrigger className="group hover:bg-muted/30 flex w-full items-center gap-3 px-3 py-3 text-left text-sm transition-colors">
                            <ChevronDown className="text-muted-foreground size-4 shrink-0 transition-transform group-data-[state=open]:rotate-180" />
                            <div className="min-w-0 flex-1">
                                <p className="font-medium tabular-nums">{entry.label}</p>
                                {entry.original_filename ? (
                                    <p
                                        className="text-muted-foreground truncate text-xs"
                                        title={entry.original_filename}
                                    >
                                        {entry.original_filename}
                                    </p>
                                ) : null}
                            </div>
                            <div className="hidden gap-4 text-right sm:flex">
                                <div>
                                    <p className="text-muted-foreground text-xs">
                                        {t('investments.import_history_positions')}
                                    </p>
                                    <p className="tabular-nums">{entry.positions_count}</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs">
                                        {t('investments.import_history_value')}
                                    </p>
                                    <p className="font-medium tabular-nums">
                                        {formatCurrency(entry.total_market_value, {
                                            precise: true,
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs">
                                        {t('investments.import_history_change')}
                                    </p>
                                    {entry.change_pct !== null ? (
                                        <p
                                            className={cn(
                                                'font-medium tabular-nums',
                                                entry.change_pct >= 0
                                                    ? 'text-primary'
                                                    : 'text-destructive',
                                            )}
                                        >
                                            {formatPercent(entry.change_pct)}
                                        </p>
                                    ) : (
                                        <p className="text-muted-foreground">—</p>
                                    )}
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-destructive shrink-0"
                                aria-label={t('investments.import_delete')}
                                onClick={(event) => {
                                    event.stopPropagation();
                                    setDeleteTarget(entry);
                                }}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </CollapsibleTrigger>
                        <CollapsibleContent className="border-t border-white/10 px-3 py-3">
                            <ImportLinesTable lines={entry.lines} />
                        </CollapsibleContent>
                    </Collapsible>
                ))}
            </div>

            <Dialog
                open={deleteTarget !== null}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('investments.import_delete')}</DialogTitle>
                        <DialogDescription>
                            {deleteTarget
                                ? t('investments.import_delete_confirm', {
                                      label: deleteTarget.label,
                                  })
                                : ''}
                            {deleteTarget ? (
                                <span className="mt-2 block">
                                    {isLatestEntry
                                        ? t('investments.import_delete_latest_hint')
                                        : t('investments.import_delete_history_hint')}
                                </span>
                            ) : null}
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
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={submitDelete}
                            disabled={deleting}
                        >
                            {t('investments.import_delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
