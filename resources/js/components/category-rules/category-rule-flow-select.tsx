import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import type { CategoryRuleFlow } from '@/types/category-rule.types';

const FLOW_ANY = '__any__';

type CategoryRuleFlowSelectProps = {
    value: CategoryRuleFlow;
    onChange: (value: CategoryRuleFlow) => void;
    id?: string;
};

export function CategoryRuleFlowSelect({
    value,
    onChange,
    id,
}: CategoryRuleFlowSelectProps) {
    const { t } = useTranslation();

    return (
        <Select
            value={value ?? FLOW_ANY}
            onValueChange={(next) =>
                onChange(next === FLOW_ANY ? null : (next as CategoryRuleFlow))
            }
        >
            <SelectTrigger id={id}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={FLOW_ANY}>
                    {t('category_rules.flow_any')}
                </SelectItem>
                <SelectItem value="credit">
                    {t('category_rules.flow_credit')}
                </SelectItem>
                <SelectItem value="debit">
                    {t('category_rules.flow_debit')}
                </SelectItem>
            </SelectContent>
        </Select>
    );
}

export function flowFromAmount(amount: number): CategoryRuleFlow {
    if (amount > 0) {
        return 'credit';
    }

    if (amount < 0) {
        return 'debit';
    }

    return null;
}

export function categoryRuleFlowLabel(
    flow: CategoryRuleFlow,
    t: (key: string) => string,
): string {
    if (flow === 'credit') {
        return t('category_rules.flow_credit');
    }

    if (flow === 'debit') {
        return t('category_rules.flow_debit');
    }

    return t('category_rules.flow_any');
}
