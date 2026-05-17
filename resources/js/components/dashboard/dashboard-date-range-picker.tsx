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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatDashboardDateRangeLabel } from '@/lib/dashboard-date-range-label';
import { dashboardUrl } from '@/lib/dashboard-query';
import { cn } from '@/lib/utils';
import type { DashboardDateRange } from '@/types/dashboard.types';

type DashboardDateRangePickerProps = {
    dateRange: DashboardDateRange;
    className?: string;
};

const PRESET_VALUES = ['3m', '6m', '12m', 'ytd', 'all', 'custom'] as const;

export function DashboardDateRangePicker({
    dateRange,
    className,
}: DashboardDateRangePickerProps) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);
    const dateLocale = locale === 'fr' ? fr : enUS;

    const selectedRange = useMemo<DateRange>(
        () => ({
            from: parseISO(dateRange.from),
            to: parseISO(dateRange.to),
        }),
        [dateRange.from, dateRange.to],
    );

    const [pendingRange, setPendingRange] = useState<DateRange | undefined>(
        selectedRange,
    );

    const label = formatDashboardDateRangeLabel(dateRange, t, dateLocale);

    const navigate = (next: DashboardDateRange) => {
        router.get(dashboardUrl(next), {}, { preserveScroll: true });
    };

    const onPresetChange = (preset: string) => {
        if (preset === 'custom') {
            setOpen(true);

            return;
        }

        if (!PRESET_VALUES.includes(preset as (typeof PRESET_VALUES)[number])) {
            return;
        }

        navigate({
            preset: preset as DashboardDateRange['preset'],
            from: dateRange.from,
            to: dateRange.to,
        });
    };

    const applyCustomRange = () => {
        if (!pendingRange?.from || !pendingRange?.to) {
            return;
        }

        navigate({
            preset: 'custom',
            from: format(pendingRange.from, 'yyyy-MM-dd'),
            to: format(pendingRange.to, 'yyyy-MM-dd'),
        });
        setOpen(false);
    };

    return (
        <div className={cn('flex items-center gap-2', className)}>
            <Select value={dateRange.preset} onValueChange={onPresetChange}>
                <SelectTrigger className="w-[140px]" size="sm">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {PRESET_VALUES.filter((value) => value !== 'custom').map((value) => (
                        <SelectItem key={value} value={value}>
                            {t(`dashboard.range_${value}`)}
                        </SelectItem>
                    ))}
                    <SelectItem value="custom">{t('dashboard.range_custom')}</SelectItem>
                </SelectContent>
            </Select>
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
                        className="max-w-[240px] justify-start font-normal"
                    >
                        <CalendarIcon className="mr-2 size-4 shrink-0" />
                        <span className="truncate">{label}</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="end">
                    <Calendar
                        mode="range"
                        locale={dateLocale}
                        captionLayout="dropdown"
                        defaultMonth={pendingRange?.from ?? selectedRange.from}
                        selected={pendingRange}
                        onSelect={setPendingRange}
                        numberOfMonths={2}
                        endMonth={new Date()}
                        disabled={{ after: new Date() }}
                    />
                    <div className="flex justify-end border-t p-3">
                        <Button
                            type="button"
                            size="sm"
                            disabled={!pendingRange?.from || !pendingRange?.to}
                            onClick={applyCustomRange}
                        >
                            {t('dashboard.range_apply')}
                        </Button>
                    </div>
                </PopoverContent>
            </Popover>
        </div>
    );
}
