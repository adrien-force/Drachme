import type { CategorySelectOption } from '@/types/category.types';
import type { TransactionFlow } from '@/types/account.types';

export type CategoryRuleFlow = TransactionFlow | null;

export type CategoryRuleRecord = {
    id: number;
    pattern: string;
    flow: CategoryRuleFlow;
    priority: number;
    is_active: boolean;
    category_id: number;
    category_name: string | null;
    category_color: string | null;
};

export type CategoryRulesIndexPageProps = {
    rules: CategoryRuleRecord[];
    categoryOptions: CategorySelectOption[];
};
