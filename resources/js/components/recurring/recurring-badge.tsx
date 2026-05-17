import { Repeat } from 'lucide-react';

import { useTranslation } from '@/hooks/use-translation';

type RecurringBadgeProps = {
    label: string;
};

export function RecurringBadge({ label }: RecurringBadgeProps) {
    const { t } = useTranslation();

    return (
        <span
            className="bg-primary/15 text-primary ml-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
            title={t('recurring.badge_title', { label })}
        >
            <Repeat className="size-3" />
            {t('recurring.badge')}
        </span>
    );
}
