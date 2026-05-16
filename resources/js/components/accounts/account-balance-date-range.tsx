import { router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { CalendarIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { DateRange } from 'react-day-picker';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useTranslation } from '@/hooks/use-translation';
import { buildAccountShowQuery } from '@/lib/account-show-query';
import { cn } from '@/lib/utils';
import type {
    AccountBalanceHistory,
    AccountTransactionFilters,
} from '@/types/account.types';

type AccountBalanceDateRangeProps = {
    accountId: number;
    balanceHistory: AccountBalanceHistory;
    transactionFilters: AccountTransactionFilters;
    className?: string;
};

function parseDate(value: string): Date {
    return parseISO(value);
}

export function AccountBalanceDateRange({
    accountId,
    balanceHistory,
    transactionFilters,
    className,
}: AccountBalanceDateRangeProps) {
    const { from, to, is_all_time: isAllTime } = balanceHistory;
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);

    const dateLocale = locale === 'fr' ? fr : enUS;

    const selectedRange = useMemo<DateRange>(
        () => ({
            from: parseDate(from),
            to: parseDate(to),
        }),
        [from, to],
    );

    const [pendingRange, setPendingRange] = useState<DateRange | undefined>(
        selectedRange,
    );

    const label = isAllTime
        ? t('accounts.balance_chart.all_time')
        : `${format(parseDate(from), 'PP', { locale: dateLocale })} – ${format(parseDate(to), 'PP', { locale: dateLocale })}`;

    const navigate = (chart: Pick<AccountBalanceHistory, 'from' | 'to' | 'is_all_time'>) => {
        router.get(
            `/accounts/${accountId}`,
            buildAccountShowQuery(chart, transactionFilters),
            { preserveState: true, preserveScroll: true },
        );
        setOpen(false);
    };

    const applyRange = () => {
        if (!pendingRange?.from || !pendingRange?.to) {
            return;
        }

        const fromStr = format(pendingRange.from, 'yyyy-MM-dd');
        const toStr = format(pendingRange.to, 'yyyy-MM-dd');

        if (!isAllTime && fromStr === from && toStr === to) {
            setOpen(false);

            return;
        }

        navigate({ from: fromStr, to: toStr, is_all_time: false });
    };

    const applyAllTime = () => {
        if (isAllTime) {
            setOpen(false);

            return;
        }

        navigate({ from, to, is_all_time: true });
    };

    return (
        <Popover
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);
                if (nextOpen) {
                    setPendingRange(selectedRange);
                }
            }}
        >
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className={cn(
                        'justify-start text-left font-normal',
                        className,
                    )}
                >
                    <CalendarIcon className="mr-2 size-4" />
                    {label}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="end">
                <Calendar
                    mode="range"
                    locale={dateLocale}
                    defaultMonth={pendingRange?.from ?? selectedRange.from}
                    selected={pendingRange}
                    onSelect={setPendingRange}
                    numberOfMonths={2}
                    disabled={{ after: new Date() }}
                />
                <div className="flex items-center justify-between gap-2 border-t p-3">
                    <Button
                        type="button"
                        variant={isAllTime ? 'secondary' : 'ghost'}
                        size="sm"
                        onClick={applyAllTime}
                    >
                        {t('accounts.balance_chart.all_time')}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        disabled={!pendingRange?.from || !pendingRange?.to}
                        onClick={applyRange}
                    >
                        {t('accounts.balance_chart.apply_range')}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
