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

/** Root ids that must be expanded to reveal `categoryId`. */
export function selectOptionAncestorRootIds(
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
