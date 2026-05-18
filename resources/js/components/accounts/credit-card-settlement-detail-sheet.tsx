import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';

import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type {
    CreditCardOpenPeriod,
    CreditCardSettlement,
} from '@/types/account.types';

type SettlementDetail = CreditCardSettlement | (CreditCardOpenPeriod & { label?: string });

type CreditCardSettlementDetailSheetProps = {
    detail: SettlementDetail | null;
    variant: 'settlement' | 'open_period';
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

function isSettlement(detail: SettlementDetail): detail is CreditCardSettlement {
    return 'id' in detail;
}

export function CreditCardSettlementDetailSheet({
    detail,
    variant,
    open,
    onOpenChange,
}: CreditCardSettlementDetailSheetProps) {
    const { t, locale } = useTranslation();
    const dateLocale = locale === 'fr' ? fr : enUS;

    if (detail === null) {
        return null;
    }

    const periodLabel = `${format(parseISO(detail.period_start), 'd MMM yyyy', { locale: dateLocale })} → ${format(parseISO(detail.period_end), 'd MMM yyyy', { locale: dateLocale })}`;

    const title =
        variant === 'open_period'
            ? t('accounts.credit_card_settlements.open_period_title')
            : isSettlement(detail)
              ? format(parseISO(detail.date), 'MMMM yyyy', { locale: dateLocale })
              : '';

    const headerAmount =
        variant === 'settlement' && isSettlement(detail)
            ? detail.amount
            : detail.spend_total;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="flex w-full flex-col gap-0 sm:max-w-md">
                <SheetHeader>
                    <SheetTitle className="capitalize">{title}</SheetTitle>
                    <SheetDescription>{periodLabel}</SheetDescription>
                </SheetHeader>

                <div className="mt-4 space-y-4 overflow-y-auto pr-1">
                    <div className="rounded-lg border border-border/60 bg-muted/20 p-4">
                        <p className="text-muted-foreground text-xs uppercase tracking-wide">
                            {variant === 'open_period'
                                ? t('accounts.credit_card_settlements.open_spend')
                                : t('accounts.credit_card_settlements.settlement_amount')}
                        </p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">
                            {formatCurrency(headerAmount, { precise: true })}
                        </p>
                        {variant === 'settlement' && isSettlement(detail) ? (
                            <div className="text-muted-foreground mt-3 space-y-1 text-sm">
                                <p>{detail.label}</p>
                                {detail.checking_label ? (
                                    <p>
                                        {t('accounts.credit_card_settlements.checking_debit', {
                                            label: detail.checking_label,
                                        })}
                                    </p>
                                ) : null}
                                <p>
                                    {t('accounts.credit_card_settlements.purchases_total', {
                                        amount: formatCurrency(detail.spend_total, {
                                            precise: true,
                                        }),
                                        count: detail.purchase_count,
                                    })}
                                </p>
                                {isSettlement(detail) && !detail.spend_matches_settlement ? (
                                    <p className="text-amber-600 dark:text-amber-400 text-xs">
                                        {t('accounts.credit_card_settlements.spend_mismatch')}
                                    </p>
                                ) : null}
                                {isSettlement(detail) && detail.period_start_is_manual ? (
                                    <p className="text-muted-foreground text-xs">
                                        {t('accounts.credit_card_settlements.period_manual')}
                                    </p>
                                ) : null}
                            </div>
                        ) : null}
                    </div>

                    <div>
                        <h3 className="mb-2 text-sm font-semibold">
                            {t('accounts.credit_card_settlements.purchases_title')}
                        </h3>
                        {detail.purchases.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('accounts.credit_card_settlements.no_purchases')}
                            </p>
                        ) : (
                            <ul className="divide-border/60 divide-y rounded-lg border">
                                {detail.purchases.map((purchase) => (
                                    <li
                                        key={purchase.id}
                                        className="flex items-start justify-between gap-3 px-3 py-2.5"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {purchase.label}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {format(parseISO(purchase.date), 'd MMM yyyy', {
                                                    locale: dateLocale,
                                                })}
                                                {purchase.category_name
                                                    ? ` · ${purchase.category_name}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <span className="shrink-0 text-sm font-medium tabular-nums text-destructive">
                                            {formatCurrency(Math.abs(purchase.amount), {
                                                precise: true,
                                            })}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
