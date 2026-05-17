import type { CategorySelectOption } from '@/types/category.types';

export type CategoryRuleRecord = {
    id: number;
    pattern: string;
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
