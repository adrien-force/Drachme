import { Check, ChevronDown, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    buildSelectOptionTree,
    selectOptionAncestorRootIds,
} from '@/lib/category-tree';
import { cn } from '@/lib/utils';
import type { CategorySelectOption, CategorySelectTreeNode } from '@/types/category.types';

const NONE_VALUE = '__none__';
const UNCATEGORIZED_VALUE = 'uncategorized';

type CategorySelectProps = {
    value: number | null;
    onChange: (value: number | null) => void;
    options: CategorySelectOption[];
    placeholder?: string;
    noneLabel?: string;
    uncategorizedLabel?: string;
    showUncategorizedOption?: boolean;
    id?: string;
};

function CategoryColorDot({ color }: { color: string | null }) {
    return (
        <span
            className="size-2.5 shrink-0 rounded-full"
            style={{ backgroundColor: color ?? 'var(--muted-foreground)' }}
            aria-hidden
        />
    );
}

type CategoryTreeRowProps = {
    node: CategorySelectTreeNode;
    depth: number;
    value: number | null;
    expandedRootIds: Set<number>;
    onToggleRoot: (rootId: number, open: boolean) => void;
    onSelect: (id: number) => void;
};

function CategoryTreeRow({
    node,
    depth,
    value,
    expandedRootIds,
    onToggleRoot,
    onSelect,
}: CategoryTreeRowProps) {
    const isSelected = value === node.id;
    const hasChildren = node.children.length > 0;
    const isRoot = depth === 0;
    const isExpanded = isRoot && expandedRootIds.has(node.id);

    const rowButton = (
        <button
            type="button"
            onClick={() => onSelect(node.id)}
            className={cn(
                'hover:bg-muted/60 flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors',
                isSelected && 'bg-primary/10 text-primary',
            )}
        >
            <CategoryColorDot color={node.color} />
            <span className="truncate">{node.name}</span>
            {isSelected ? <Check className="text-primary ml-auto size-4 shrink-0" /> : null}
        </button>
    );

    if (isRoot) {
        const chevronSlot = hasChildren ? (
            <CollapsibleTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-7 shrink-0"
                    aria-label={isExpanded ? 'Réduire' : 'Développer'}
                >
                    <ChevronRight
                        className={cn(
                            'size-4 transition-transform',
                            isExpanded && 'rotate-90',
                        )}
                    />
                </Button>
            </CollapsibleTrigger>
        ) : (
            <span
                className="inline-flex size-7 shrink-0 items-center justify-center"
                aria-hidden
            >
                <ChevronRight className="text-muted-foreground/35 size-4" />
            </span>
        );

        const header = (
            <div className="flex items-center gap-0.5">
                {chevronSlot}
                {rowButton}
            </div>
        );

        if (!hasChildren) {
            return header;
        }

        return (
            <Collapsible
                open={isExpanded}
                onOpenChange={(open) => onToggleRoot(node.id, open)}
            >
                {header}
                <CollapsibleContent>
                    {node.children.map((child) => (
                        <CategoryTreeRow
                            key={child.id}
                            node={child}
                            depth={1}
                            value={value}
                            expandedRootIds={expandedRootIds}
                            onToggleRoot={onToggleRoot}
                            onSelect={onSelect}
                        />
                    ))}
                </CollapsibleContent>
            </Collapsible>
        );
    }

    return (
        <div>
            <div style={{ paddingLeft: `${depth * 0.75}rem` }}>{rowButton}</div>
            {hasChildren
                ? node.children.map((child) => (
                      <CategoryTreeRow
                          key={child.id}
                          node={child}
                          depth={depth + 1}
                          value={value}
                          expandedRootIds={expandedRootIds}
                          onToggleRoot={onToggleRoot}
                          onSelect={onSelect}
                      />
                  ))
                : null}
        </div>
    );
}

export function CategorySelect({
    value,
    onChange,
    options,
    placeholder,
    noneLabel = '—',
    uncategorizedLabel,
    showUncategorizedOption = false,
    id,
}: CategorySelectProps) {
    const [open, setOpen] = useState(false);
    const tree = useMemo(() => buildSelectOptionTree(options), [options]);

    const selectedOption = useMemo(
        () => options.find((option) => option.id === value) ?? null,
        [options, value],
    );

    const [expandedRootIds, setExpandedRootIds] = useState<Set<number>>(() => new Set());

    useEffect(() => {
        if (!open) {
            return;
        }

        const ancestors = selectOptionAncestorRootIds(tree, value);
        setExpandedRootIds(new Set(ancestors));
    }, [open, tree, value]);

    const toggleRoot = (rootId: number, isOpen: boolean) => {
        setExpandedRootIds((current) => {
            const next = new Set(current);
            if (isOpen) {
                next.add(rootId);
            } else {
                next.delete(rootId);
            }

            return next;
        });
    };

    const pick = (next: number | null) => {
        onChange(next);
        setOpen(false);
    };

    const triggerLabel =
        value === null
            ? showUncategorizedOption && uncategorizedLabel
                ? uncategorizedLabel
                : noneLabel
            : (selectedOption?.name ?? placeholder ?? noneLabel);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="h-9 w-full justify-between font-normal"
                >
                    <span className="flex min-w-0 items-center gap-2">
                        {selectedOption ? (
                            <CategoryColorDot color={selectedOption.color} />
                        ) : null}
                        <span className="truncate">{triggerLabel}</span>
                    </span>
                    <ChevronDown className="text-muted-foreground size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="max-h-80 w-[var(--radix-popover-trigger-width)] overflow-y-auto p-1"
                align="start"
            >
                <button
                    type="button"
                    onClick={() => pick(null)}
                    className={cn(
                        'hover:bg-muted/60 flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm',
                        value === null &&
                            !showUncategorizedOption &&
                            'bg-primary/10 text-primary',
                    )}
                >
                    <span className="text-muted-foreground">{noneLabel}</span>
                    {value === null && !showUncategorizedOption ? (
                        <Check className="text-primary ml-auto size-4" />
                    ) : null}
                </button>
                {showUncategorizedOption && uncategorizedLabel ? (
                    <button
                        type="button"
                        onClick={() => pick(null)}
                        className={cn(
                            'hover:bg-muted/60 flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm',
                            value === null && 'bg-primary/10 text-primary',
                        )}
                    >
                        <span>{uncategorizedLabel}</span>
                        {value === null ? (
                            <Check className="text-primary ml-auto size-4" />
                        ) : null}
                    </button>
                ) : null}
                <div className="border-border/60 my-1 border-t" />
                {tree.map((node) => (
                    <CategoryTreeRow
                        key={node.id}
                        node={node}
                        depth={0}
                        value={value}
                        expandedRootIds={expandedRootIds}
                        onToggleRoot={toggleRoot}
                        onSelect={pick}
                    />
                ))}
            </PopoverContent>
        </Popover>
    );
}

export type CategoryFilterValue = string | null;

type CategoryFilterSelectProps = {
    value: CategoryFilterValue;
    onChange: (value: CategoryFilterValue) => void;
    options: CategorySelectOption[];
    allLabel: string;
    uncategorizedLabel: string;
    id?: string;
};

export function CategoryFilterSelect({
    value,
    onChange,
    options,
    allLabel,
    uncategorizedLabel,
    id,
}: CategoryFilterSelectProps) {
    const [open, setOpen] = useState(false);
    const tree = useMemo(() => buildSelectOptionTree(options), [options]);

    const isAll = value === null;
    const isUncategorized = value === UNCATEGORIZED_VALUE;
    const selectedCategoryId =
        !isAll && !isUncategorized && value !== null
            ? Number.parseInt(value, 10)
            : null;
    const selectedCategory = useMemo(
        () =>
            selectedCategoryId !== null && !Number.isNaN(selectedCategoryId)
                ? (options.find((option) => option.id === selectedCategoryId) ?? null)
                : null,
        [options, selectedCategoryId],
    );

    const [expandedRootIds, setExpandedRootIds] = useState<Set<number>>(() => new Set());

    useEffect(() => {
        if (!open) {
            return;
        }

        const ancestors = selectOptionAncestorRootIds(tree, selectedCategoryId);
        setExpandedRootIds(new Set(ancestors));
    }, [open, tree, selectedCategoryId]);

    const toggleRoot = (rootId: number, isOpen: boolean) => {
        setExpandedRootIds((current) => {
            const next = new Set(current);
            if (isOpen) {
                next.add(rootId);
            } else {
                next.delete(rootId);
            }

            return next;
        });
    };

    const pickCategory = (categoryId: number) => {
        onChange(String(categoryId));
        setOpen(false);
    };

    const triggerLabel = isAll
        ? allLabel
        : isUncategorized
          ? uncategorizedLabel
          : (selectedCategory?.name ?? allLabel);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="h-9 w-full justify-between font-normal"
                >
                    <span className="flex min-w-0 items-center gap-2">
                        {selectedCategory ? (
                            <CategoryColorDot color={selectedCategory.color} />
                        ) : null}
                        <span className="truncate">{triggerLabel}</span>
                    </span>
                    <ChevronDown className="text-muted-foreground size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="max-h-80 w-[var(--radix-popover-trigger-width)] overflow-y-auto p-1"
                align="start"
            >
                <button
                    type="button"
                    onClick={() => {
                        onChange(null);
                        setOpen(false);
                    }}
                    className={cn(
                        'hover:bg-muted/60 flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm',
                        isAll && 'bg-primary/10 text-primary',
                    )}
                >
                    <span>{allLabel}</span>
                    {isAll ? <Check className="text-primary ml-auto size-4" /> : null}
                </button>
                <button
                    type="button"
                    onClick={() => {
                        onChange(UNCATEGORIZED_VALUE);
                        setOpen(false);
                    }}
                    className={cn(
                        'hover:bg-muted/60 flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm',
                        isUncategorized && 'bg-primary/10 text-primary',
                    )}
                >
                    <span>{uncategorizedLabel}</span>
                    {isUncategorized ? (
                        <Check className="text-primary ml-auto size-4" />
                    ) : null}
                </button>
                <div className="border-border/60 my-1 border-t" />
                {tree.map((node) => (
                    <CategoryTreeRow
                        key={node.id}
                        node={node}
                        depth={0}
                        value={selectedCategoryId}
                        expandedRootIds={expandedRootIds}
                        onToggleRoot={toggleRoot}
                        onSelect={pickCategory}
                    />
                ))}
            </PopoverContent>
        </Popover>
    );
}

export { NONE_VALUE as CATEGORY_SELECT_NONE, UNCATEGORIZED_VALUE as CATEGORY_FILTER_UNCATEGORIZED };
