import { format, parseISO } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { CalendarIcon, X } from 'lucide-react';
import { useMemo, useState, type MouseEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export type DatePickerProps = {
    id?: string;
    name?: string;
    value: string | null;
    onChange: (value: string | null) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    clearable?: boolean;
};

function parseDateValue(value: string): Date | undefined {
    if (value === '') {
        return undefined;
    }

    const parsed = parseISO(value);

    return Number.isNaN(parsed.getTime()) ? undefined : parsed;
}

export function DatePicker({
    id,
    name,
    value,
    onChange,
    placeholder,
    disabled = false,
    className,
    clearable = false,
}: DatePickerProps) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);

    const dateLocale = locale === 'fr' ? fr : enUS;
    const selected = useMemo(() => parseDateValue(value ?? ''), [value]);

    const displayLabel =
        selected !== undefined
            ? format(selected, 'PP', { locale: dateLocale })
            : (placeholder ?? t('common.pick_date'));

    const pick = (date: Date | undefined) => {
        onChange(date ? format(date, 'yyyy-MM-dd') : null);
        setOpen(false);
    };

    const clear = (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        onChange(null);
    };

    return (
        <div className={cn('flex gap-1', className)}>
            {name ? <input type="hidden" name={name} value={value ?? ''} /> : null}
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        className={cn(
                            'h-9 min-w-0 flex-1 justify-start px-3 font-normal',
                            !selected && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon className="mr-2 size-4 shrink-0" />
                        <span className="truncate">{displayLabel}</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        locale={dateLocale}
                        captionLayout="dropdown"
                        defaultMonth={selected ?? new Date()}
                        selected={selected}
                        onSelect={pick}
                    />
                </PopoverContent>
            </Popover>
            {clearable && value ? (
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="size-9 shrink-0"
                    disabled={disabled}
                    aria-label={t('common.clear')}
                    onClick={(event: MouseEvent) => clear(event)}
                >
                    <X className="size-4" />
                </Button>
            ) : null}
        </div>
    );
}
