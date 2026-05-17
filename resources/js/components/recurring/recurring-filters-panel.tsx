import { router } from '@inertiajs/react';
import { ArrowDownAZ, ArrowUpAZ } from 'lucide-react';

import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import {
    recurringFiltersWithDefaults,
    recurringIndexQuery,
} from '@/lib/recurring-index-query';
import { recurringFrequencyLabel } from '@/lib/recurring-frequency';
import type {
    RecurringFlow,
    RecurringFrequency,
    RecurringListFilters,
    RecurringSortColumn,
} from '@/types/recurring.types';

type RecurringFiltersPanelProps = {
    filters: RecurringListFilters;
    perPageOptions: number[];
    frequencyOptions: RecurringFrequency[];
};

export function RecurringFiltersPanel({
    filters,
    perPageOptions,
    frequencyOptions,
}: RecurringFiltersPanelProps) {
    const { t } = useTranslation();

    const navigate = (patch: Parameters<typeof recurringIndexQuery>[1]) => {
        router.get(
            '/recurring',
            recurringIndexQuery(filters, recurringFiltersWithDefaults(patch ?? {})),
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const hasActiveFilters =
        filters.search.trim() !== '' ||
        filters.flow !== null ||
        filters.frequency !== null;

    return (
        <GlassPanel className="space-y-4 p-4 md:p-6">
            <h2 className="text-lg font-semibold">{t('recurring.filters_title')}</h2>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="space-y-2 md:col-span-2 xl:col-span-2">
                    <Label htmlFor="recurring-search">{t('recurring.filter_search')}</Label>
                    <Input
                        id="recurring-search"
                        value={filters.search}
                        placeholder={t('recurring.search_placeholder')}
                        onChange={(event) =>
                            navigate({ search: event.target.value, confirmed_page: 1, suggestions_page: 1 })
                        }
                    />
                </div>
                <div className="space-y-2">
                    <Label>{t('recurring.filter_flow')}</Label>
                    <Select
                        value={filters.flow ?? 'all'}
                        onValueChange={(value) =>
                            navigate({
                                flow: value === 'all' ? null : (value as RecurringFlow),
                                confirmed_page: 1,
                                suggestions_page: 1,
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t('recurring.flow_all')}</SelectItem>
                            <SelectItem value="debit">{t('recurring.flow_debit')}</SelectItem>
                            <SelectItem value="credit">{t('recurring.flow_credit')}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-2">
                    <Label>{t('recurring.filter_frequency')}</Label>
                    <Select
                        value={filters.frequency ?? 'all'}
                        onValueChange={(value) =>
                            navigate({
                                frequency: value === 'all' ? null : (value as RecurringFrequency),
                                confirmed_page: 1,
                                suggestions_page: 1,
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t('recurring.frequency_all')}</SelectItem>
                            {frequencyOptions.map((frequency) => (
                                <SelectItem key={frequency} value={frequency}>
                                    {recurringFrequencyLabel(frequency, t)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-2">
                    <Label>{t('recurring.sort')}</Label>
                    <Select
                        value={filters.sort}
                        onValueChange={(value) =>
                            navigate({
                                sort: value as RecurringSortColumn,
                                confirmed_page: 1,
                                suggestions_page: 1,
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="amount">{t('recurring.sort_amount')}</SelectItem>
                            <SelectItem value="label">{t('recurring.sort_label')}</SelectItem>
                            <SelectItem value="frequency">{t('recurring.sort_frequency')}</SelectItem>
                            <SelectItem value="occurrences">
                                {t('recurring.sort_occurrences')}
                            </SelectItem>
                            <SelectItem value="last_seen">{t('recurring.sort_last_seen')}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-2">
                    <Label>{t('recurring.order')}</Label>
                    <Button
                        type="button"
                        variant="outline"
                        className="w-full justify-start"
                        onClick={() =>
                            navigate({
                                order: filters.order === 'asc' ? 'desc' : 'asc',
                                confirmed_page: 1,
                                suggestions_page: 1,
                            })
                        }
                    >
                        {filters.order === 'asc' ? (
                            <ArrowUpAZ className="mr-2 size-4" />
                        ) : (
                            <ArrowDownAZ className="mr-2 size-4" />
                        )}
                        {filters.order === 'asc'
                            ? t('recurring.order_asc')
                            : t('recurring.order_desc')}
                    </Button>
                </div>
                <div className="space-y-2">
                    <Label>{t('recurring.per_page')}</Label>
                    <Select
                        value={String(filters.per_page)}
                        onValueChange={(value) =>
                            navigate({
                                per_page: Number.parseInt(value, 10),
                                confirmed_page: 1,
                                suggestions_page: 1,
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {perPageOptions.map((option) => (
                                <SelectItem key={option} value={String(option)}>
                                    {t('recurring.per_page_option', { count: option })}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            {hasActiveFilters ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() =>
                        router.get('/recurring', {
                            per_page: filters.per_page,
                        })
                    }
                >
                    {t('recurring.reset_filters')}
                </Button>
            ) : null}
        </GlassPanel>
    );
}
