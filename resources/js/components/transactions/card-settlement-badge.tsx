import { CreditCard } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type CardSettlementBadgeProps = {
    className?: string;
};

export function CardSettlementBadge({ className }: CardSettlementBadgeProps) {
    const { t } = useTranslation();

    return (
        <Badge
            variant="outline"
            className={cn(
                'border-primary/40 bg-primary/10 text-primary gap-1 font-normal',
                className,
            )}
        >
            <CreditCard className="size-3" aria-hidden />
            {t('transactions.card_settlement_badge')}
        </Badge>
    );
}
