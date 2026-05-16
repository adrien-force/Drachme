import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';
import { accountTypeBadgeClass } from '@/lib/account-type-styles';
import { cn } from '@/lib/utils';
import type { AccountType } from '@/types/account.types';

type AccountTypeBadgeProps = {
    type: AccountType;
    className?: string;
};

export function AccountTypeBadge({ type, className }: AccountTypeBadgeProps) {
    const { t } = useTranslation();

    return (
        <Badge
            variant="outline"
            className={cn(accountTypeBadgeClass[type], className)}
        >
            {t(`accounts.types.${type}`)}
        </Badge>
    );
}
