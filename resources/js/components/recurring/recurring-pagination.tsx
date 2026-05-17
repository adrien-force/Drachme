import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import type { PaginatedMeta } from '@/types/account.types';

type RecurringPaginationProps = {
    meta: PaginatedMeta;
    onPageChange: (page: number) => void;
};

export function RecurringPagination({ meta, onPageChange }: RecurringPaginationProps) {
    const { t } = useTranslation();

    if (meta.total === 0) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-muted-foreground text-sm">
                {meta.from !== null && meta.to !== null
                    ? t('recurring.pagination_summary', {
                          from: meta.from,
                          to: meta.to,
                          total: meta.total,
                      })
                    : t('recurring.pagination_empty')}
            </p>
            <div className="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    disabled={meta.current_page <= 1}
                    onClick={() => onPageChange(meta.current_page - 1)}
                >
                    <ChevronLeft className="size-4" />
                    <span className="sr-only">{t('recurring.previous')}</span>
                </Button>
                <span className="text-muted-foreground text-sm tabular-nums">
                    {t('recurring.page_of', {
                        page: meta.current_page,
                        last: meta.last_page,
                    })}
                </span>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    disabled={meta.current_page >= meta.last_page}
                    onClick={() => onPageChange(meta.current_page + 1)}
                >
                    <ChevronRight className="size-4" />
                    <span className="sr-only">{t('recurring.next')}</span>
                </Button>
            </div>
        </div>
    );
}
