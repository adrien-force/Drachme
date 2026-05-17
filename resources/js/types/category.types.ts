export type CategoryRecord = {
    id: number;
    parent_id: number | null;
    name: string;
    color: string | null;
    icon: string | null;
    sort_order: number;
    is_system: boolean;
    is_uncategorized: boolean;
    transactions_count: number;
};

export type CategorySelectOption = {
    id: number;
    parent_id: number | null;
    name: string;
    depth: number;
    color: string | null;
    is_system: boolean;
    sort_order: number;
};

export type CategorySelectTreeNode = CategorySelectOption & {
    children: CategorySelectTreeNode[];
};

export type CategoriesIndexPageProps = {
    categories: CategoryRecord[];
};

export type CategoryTreeNode = CategoryRecord & {
    depth: number;
    children: CategoryTreeNode[];
};
