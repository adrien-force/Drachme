import { formatCurrency } from '@/lib/format-currency';
import { cn } from '@/lib/utils';
import type { NormalizedPreviewRow } from '@/types/provider.types';
import { useTranslation } from '@/hooks/use-translation';

type ProviderNormalizedPreviewTableProps = {
    rows: NormalizedPreviewRow[];
    mapsBalance: boolean;
};

export function ProviderNormalizedPreviewTable({
    rows,
    mapsBalance,
}: ProviderNormalizedPreviewTableProps) {
    const { t } = useTranslation();

    if (rows.length === 0) {
        return null;
    }

    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="w-full min-w-[480px] border-collapse text-sm">
                <thead>
                    <tr className="bg-muted/50 border-b text-left">
                        <th className="text-muted-foreground w-12 px-3 py-2 text-xs font-medium uppercase">
                            #
                        </th>
                        <th className="px-3 py-2 text-xs font-medium uppercase">
                            {t('providers.fields.date')}
                        </th>
                        <th className="px-3 py-2 text-xs font-medium uppercase">
                            {t('providers.fields.label')}
                        </th>
                        <th className="px-3 py-2 text-right text-xs font-medium uppercase">
                            {t('providers.fields.amount_signed')}
                        </th>
                        {mapsBalance ? (
                            <th className="px-3 py-2 text-right text-xs font-medium uppercase">
                                {t('providers.fields.balance')}
                            </th>
                        ) : null}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, index) => (
                        <tr
                            key={index}
                            className={cn(
                                'border-border/50 border-b last:border-0',
                                'error' in row && 'bg-destructive/5',
                            )}
                        >
                            <td className="text-muted-foreground px-3 py-2 tabular-nums">
                                {row.line}
                            </td>
                            {'error' in row ? (
                                <td
                                    colSpan={mapsBalance ? 4 : 3}
                                    className="text-destructive px-3 py-2 text-sm"
                                >
                                    {row.error}
                                </td>
                            ) : (
                                <>
                                    <td className="px-3 py-2 whitespace-nowrap">{row.date}</td>
                                    <td className="max-w-[240px] truncate px-3 py-2" title={row.label}>
                                        {row.label}
                                    </td>
                                    <td
                                        className={cn(
                                            'px-3 py-2 text-right tabular-nums',
                                            row.amount >= 0
                                                ? 'text-chart-income'
                                                : 'text-chart-expense',
                                        )}
                                    >
                                        {formatCurrency(row.amount, { precise: true })}
                                    </td>
                                    {mapsBalance ? (
                                        <td className="px-3 py-2 text-right tabular-nums">
                                            {row.balance != null
                                                ? formatCurrency(row.balance, {
                                                      precise: true,
                                                  })
                                                : '—'}
                                        </td>
                                    ) : null}
                                </>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
