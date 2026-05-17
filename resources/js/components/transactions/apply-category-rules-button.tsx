import { router } from '@inertiajs/react';
import { Tags } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';

type ApplyCategoryRulesButtonProps = {
    uncategorizedCount: number;
    accountId?: number;
    className?: string;
};

export function ApplyCategoryRulesButton({
    uncategorizedCount,
    accountId,
    className,
}: ApplyCategoryRulesButtonProps) {
    const { t } = useTranslation();

    const applyRules = () => {
        router.post(
            '/transactions/apply-category-rules',
            accountId !== undefined ? { account_id: accountId } : {},
            { preserveScroll: true },
        );
    };

    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            className={className}
            disabled={uncategorizedCount === 0}
            onClick={applyRules}
        >
            <Tags className="mr-2 size-4" />
            {t('transactions.apply_rules_bulk', { count: uncategorizedCount })}
        </Button>
    );
}
