import type {
    CategoryRecord,
    CategorySelectOption,
    CategorySelectTreeNode,
    CategoryTreeNode,
} from '@/types/category.types';

export function buildCategoryTree(categories: CategoryRecord[]): CategoryTreeNode[] {
    const nodes = new Map<number, CategoryTreeNode>();

    for (const category of categories) {
        nodes.set(category.id, { ...category, depth: 1, children: [] });
    }

    const roots: CategoryTreeNode[] = [];

    for (const node of nodes.values()) {
        if (node.parent_id === null) {
            roots.push(node);

            continue;
        }

        const parent = nodes.get(node.parent_id);
        if (parent) {
            node.depth = parent.depth + 1;
            parent.children.push(node);
        } else {
            roots.push(node);
        }
    }

    const sortNodes = (list: CategoryTreeNode[]): void => {
        list.sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name));
        for (const child of list) {
            sortNodes(child.children);
        }
    };

    sortNodes(roots);

    return roots;
}

export function flattenCategoryTree(nodes: CategoryTreeNode[]): CategoryTreeNode[] {
    const flat: CategoryTreeNode[] = [];

    const walk = (list: CategoryTreeNode[]) => {
        for (const node of list) {
            flat.push(node);
            walk(node.children);
        }
    };

    walk(nodes);

    return flat;
}

export function buildSelectOptionTree(
    options: CategorySelectOption[],
): CategorySelectTreeNode[] {
    const nodes = new Map<number, CategorySelectTreeNode>();

    for (const option of options) {
        nodes.set(option.id, { ...option, children: [] });
    }

    const roots: CategorySelectTreeNode[] = [];

    for (const node of nodes.values()) {
        if (node.parent_id === null) {
            roots.push(node);

            continue;
        }

        const parent = nodes.get(node.parent_id);
        if (parent) {
            parent.children.push(node);
        } else {
            roots.push(node);
        }
    }

    const sortNodes = (list: CategorySelectTreeNode[]): void => {
        list.sort(
            (a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name),
        );
        for (const child of list) {
            sortNodes(child.children);
        }
    };

    sortNodes(roots);

    return roots;
}

/** Ancestor category ids that must be expanded to reveal `categoryId`. */
export function selectOptionAncestorIds(
    tree: CategorySelectTreeNode[],
    categoryId: number | null,
): number[] {
    if (categoryId === null) {
        return [];
    }

    const findPath = (
        nodes: CategorySelectTreeNode[],
        trail: number[],
    ): number[] | null => {
        for (const node of nodes) {
            const nextTrail = [...trail, node.id];

            if (node.id === categoryId) {
                return trail;
            }

            const nested = findPath(node.children, nextTrail);
            if (nested !== null) {
                return nested;
            }
        }

        return null;
    };

    return findPath(tree, []) ?? [];
}

/** @deprecated Use selectOptionAncestorIds */
export function selectOptionAncestorRootIds(
    tree: CategorySelectTreeNode[],
    categoryId: number | null,
): number[] {
    return selectOptionAncestorIds(tree, categoryId);
}

/** Case- and accent-insensitive fold for search (é, è, ê → e). */
export function normalizeSearchText(value: string): string {
    return value
        .trim()
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '');
}

function normalizeCategorySearchQuery(query: string): string {
    return normalizeSearchText(query);
}

/** Keeps nodes whose name matches or that have a matching descendant. */
export function filterSelectOptionTree(
    tree: CategorySelectTreeNode[],
    query: string,
): CategorySelectTreeNode[] {
    const normalized = normalizeCategorySearchQuery(query);
    if (normalized === '') {
        return tree;
    }

    const visit = (nodes: CategorySelectTreeNode[]): CategorySelectTreeNode[] =>
        nodes.flatMap((node) => {
            const filteredChildren = visit(node.children);
            const selfMatches = normalizeSearchText(node.name).includes(normalized);

            if (!selfMatches && filteredChildren.length === 0) {
                return [];
            }

            return [
                {
                    ...node,
                    children: selfMatches ? node.children : filteredChildren,
                },
            ];
        });

    return visit(tree);
}

export function collectSelectOptionTreeIds(tree: CategorySelectTreeNode[]): number[] {
    const ids: number[] = [];

    const walk = (nodes: CategorySelectTreeNode[]) => {
        for (const node of nodes) {
            ids.push(node.id);
            walk(node.children);
        }
    };

    walk(tree);

    return ids;
}
