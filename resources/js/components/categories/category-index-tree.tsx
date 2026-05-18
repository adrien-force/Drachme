import { ChevronRight, FolderPlus, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { CategoryRecord, CategoryTreeNode } from '@/types/category.types';

type CategoryIndexTreeProps = {
    tree: CategoryTreeNode[];
    onAddChild: (parentId: number) => void;
    onEdit: (category: CategoryRecord) => void;
    onDelete: (category: CategoryRecord) => void;
};

export function CategoryIndexTree({
    tree,
    onAddChild,
    onEdit,
    onDelete,
}: CategoryIndexTreeProps) {
    const [expandedIds, setExpandedIds] = useState<Set<number>>(() => new Set());

    const toggleExpanded = (id: number, open: boolean) => {
        setExpandedIds((current) => {
            const next = new Set(current);
            if (open) {
                next.add(id);
            } else {
                next.delete(id);
            }

            return next;
        });
    };

    return (
        <>
            {tree.map((node) => (
                <CategoryIndexTreeNode
                    key={node.id}
                    node={node}
                    expandedIds={expandedIds}
                    onToggleExpanded={toggleExpanded}
                    onAddChild={onAddChild}
                    onEdit={onEdit}
                    onDelete={onDelete}
                />
            ))}
        </>
    );
}

type CategoryIndexTreeNodeProps = {
    node: CategoryTreeNode;
    expandedIds: Set<number>;
    onToggleExpanded: (id: number, open: boolean) => void;
    onAddChild: (parentId: number) => void;
    onEdit: (category: CategoryRecord) => void;
    onDelete: (category: CategoryRecord) => void;
};

function CategoryIndexTreeNode({
    node,
    expandedIds,
    onToggleExpanded,
    onAddChild,
    onEdit,
    onDelete,
}: CategoryIndexTreeNodeProps) {
    const hasChildren = node.children.length > 0;
    const isExpanded = expandedIds.has(node.id);
    const isRoot = node.depth === 1;

    const row = (
        <CategoryIndexTreeRow
            node={node}
            hasChildren={hasChildren}
            isExpanded={isExpanded}
            isRoot={isRoot}
            onAddChild={onAddChild}
            onEdit={onEdit}
            onDelete={onDelete}
        />
    );

    if (!hasChildren) {
        return <div className="border-border/40 border-b last:border-0">{row}</div>;
    }

    return (
        <Collapsible
            open={isExpanded}
            onOpenChange={(open) => onToggleExpanded(node.id, open)}
            className="border-border/40 border-b last:border-0"
        >
            {row}
            <CollapsibleContent>
                {node.children.map((child) => (
                    <CategoryIndexTreeNode
                        key={child.id}
                        node={child}
                        expandedIds={expandedIds}
                        onToggleExpanded={onToggleExpanded}
                        onAddChild={onAddChild}
                        onEdit={onEdit}
                        onDelete={onDelete}
                    />
                ))}
            </CollapsibleContent>
        </Collapsible>
    );
}

type CategoryIndexTreeRowProps = {
    node: CategoryTreeNode;
    hasChildren: boolean;
    isExpanded: boolean;
    isRoot: boolean;
    onAddChild: (parentId: number) => void;
    onEdit: (category: CategoryRecord) => void;
    onDelete: (category: CategoryRecord) => void;
};

function CategoryIndexTreeRow({
    node,
    hasChildren,
    isExpanded,
    isRoot,
    onAddChild,
    onEdit,
    onDelete,
}: CategoryIndexTreeRowProps) {
    const { t } = useTranslation();

    const chevronSlot = hasChildren ? (
        <CollapsibleTrigger asChild>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-7 shrink-0"
                aria-label={
                    isExpanded ? t('categories.collapse') : t('categories.expand')
                }
            >
                <ChevronRight
                    className={cn(
                        'size-4 transition-transform',
                        isExpanded && 'rotate-90',
                    )}
                />
            </Button>
        </CollapsibleTrigger>
    ) : isRoot ? (
        <span
            className="inline-flex size-7 shrink-0 items-center justify-center"
            aria-hidden
        >
            <ChevronRight className="text-muted-foreground/35 size-4" />
        </span>
    ) : (
        <span className="size-7 shrink-0" aria-hidden />
    );

    return (
        <div
            className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            style={{ paddingLeft: `${0.75 * (node.depth - 1) + 1}rem` }}
        >
            <div className="flex min-w-0 flex-1 items-center gap-0.5">
                {chevronSlot}
                <div className="flex min-w-0 flex-1 items-center gap-3 pl-0.5">
                    <span
                        className="size-3 shrink-0 rounded-full"
                        style={{
                            backgroundColor: node.color ?? 'var(--muted-foreground)',
                        }}
                    />
                    <div className="min-w-0">
                        <p className="font-medium">{node.name}</p>
                        <p className="text-muted-foreground text-xs">
                            {t('categories.transactions_count', {
                                count: node.transactions_count,
                            })}
                        </p>
                    </div>
                </div>
            </div>
            {!node.is_system ? (
                <div className="flex flex-wrap gap-2 sm:pl-8">
                    {node.depth < 3 ? (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => onAddChild(node.id)}
                        >
                            <FolderPlus className="mr-1 size-4" />
                            {t('categories.add_child')}
                        </Button>
                    ) : null}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => onEdit(node)}
                    >
                        <Pencil className="mr-1 size-4" />
                        {t('categories.edit')}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="text-destructive"
                        onClick={() => onDelete(node)}
                    >
                        <Trash2 className="mr-1 size-4" />
                        {t('categories.delete')}
                    </Button>
                </div>
            ) : null}
        </div>
    );
}
