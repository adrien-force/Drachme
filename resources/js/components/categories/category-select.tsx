import { Check, ChevronDown, ChevronRight, Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    buildSelectOptionTree,
    collectSelectOptionTreeIds,
    filterSelectOptionTree,
    selectOptionAncestorIds,
} from '@/lib/category-tree';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { CategorySelectOption, CategorySelectTreeNode } from '@/types/category.types';

const NONE_VALUE = '__none__';
const UNCATEGORIZED_VALUE = 'uncategorized';

type CategorySelectPopoverShellProps = {
    open: boolean;
    searchQuery: string;
    onSearchQueryChange: (query: string) => void;
    children: ReactNode;
};

function CategorySelectPopoverShell({
    open,
    searchQuery,
    onSearchQueryChange,
    children,
}: CategorySelectPopoverShellProps) {
    const { t } = useTranslation();
    const searchInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!open) {
            onSearchQueryChange('');
            return;
        }

        const frame = requestAnimationFrame(() => {
            searchInputRef.current?.focus();
        });

        return () => cancelAnimationFrame(frame);
    }, [open, onSearchQueryChange]);

    return (
        <PopoverContent
            className="w-[var(--radix-popover-trigger-width)] p-0"
            align="start"
            collisionPadding={16}
            onOpenAutoFocus={(event) => event.preventDefault()}
            onWheel={(event) => event.stopPropagation()}
        >
            <div className="flex max-h-80 flex-col">
                <div className="border-border/60 shrink-0 border-b p-1">
                    <div className="relative">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2" />
                        <Input
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={(event) => onSearchQueryChange(event.target.value)}
                            placeholder={t('categories.search_placeholder')}
                            className="h-8 pl-8"
                            onKeyDown={(event) => {
                                if (event.key === 'Escape') {
                                    event.stopPropagation();
                                }
                            }}
                        />
                    </div>
                </div>
                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-1">
                    {children}
                </div>
            </div>
        </PopoverContent>
    );
}

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
    expandedIds: Set<number>;
    onToggleExpanded: (id: number, open: boolean) => void;
    onSelect: (id: number) => void;
};

function CategoryTreeRow({
    node,
    depth,
    value,
    expandedIds,
    onToggleExpanded,
    onSelect,
}: CategoryTreeRowProps) {
    const { t } = useTranslation();
    const isSelected = value === node.id;
    const hasChildren = node.children.length > 0;
    const isExpanded = expandedIds.has(node.id);

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
    ) : depth === 0 ? (
        <span
            className="inline-flex size-7 shrink-0 items-center justify-center"
            aria-hidden
        >
            <ChevronRight className="text-muted-foreground/35 size-4" />
        </span>
    ) : (
        <span className="size-7 shrink-0" aria-hidden />
    );

    const header = (
        <div
            className="flex min-w-0 items-center gap-0.5"
            style={depth > 0 ? { paddingLeft: `${depth * 0.75}rem` } : undefined}
        >
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
            onOpenChange={(open) => onToggleExpanded(node.id, open)}
        >
            {header}
            <CollapsibleContent>
                {node.children.map((child) => (
                    <CategoryTreeRow
                        key={child.id}
                        node={child}
                        depth={depth + 1}
                        value={value}
                        expandedIds={expandedIds}
                        onToggleExpanded={onToggleExpanded}
                        onSelect={onSelect}
                    />
                ))}
            </CollapsibleContent>
        </Collapsible>
    );
}

function useCategoryTreeExpansion(
    open: boolean,
    tree: CategorySelectTreeNode[],
    filteredTree: CategorySelectTreeNode[],
    searchQuery: string,
    selectedCategoryId: number | null,
) {
    const [expandedIds, setExpandedIds] = useState<Set<number>>(() => new Set());
    const isSearching = searchQuery.trim() !== '';

    useEffect(() => {
        if (!open) {
            return;
        }

        if (isSearching) {
            setExpandedIds(new Set(collectSelectOptionTreeIds(filteredTree)));
            return;
        }

        const ancestors = selectOptionAncestorIds(tree, selectedCategoryId);
        setExpandedIds(new Set(ancestors));
    }, [open, tree, filteredTree, isSearching, selectedCategoryId]);

    const toggleExpanded = (categoryId: number, isOpen: boolean) => {
        setExpandedIds((current) => {
            const next = new Set(current);
            if (isOpen) {
                next.add(categoryId);
            } else {
                next.delete(categoryId);
            }

            return next;
        });
    };

    return { expandedIds, toggleExpanded };
}

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
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const tree = useMemo(() => buildSelectOptionTree(options), [options]);
    const filteredTree = useMemo(
        () => filterSelectOptionTree(tree, searchQuery),
        [tree, searchQuery],
    );

    const selectedOption = useMemo(
        () => options.find((option) => option.id === value) ?? null,
        [options, value],
    );

    const { expandedIds, toggleExpanded } = useCategoryTreeExpansion(
        open,
        tree,
        filteredTree,
        searchQuery,
        value,
    );

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
            <CategorySelectPopoverShell
                open={open}
                searchQuery={searchQuery}
                onSearchQueryChange={setSearchQuery}
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
                {filteredTree.length === 0 ? (
                    <p className="text-muted-foreground px-2 py-3 text-center text-sm">
                        {t('categories.search_no_results')}
                    </p>
                ) : (
                    filteredTree.map((node) => (
                        <CategoryTreeRow
                            key={node.id}
                            node={node}
                            depth={0}
                            value={value}
                            expandedIds={expandedIds}
                            onToggleExpanded={toggleExpanded}
                            onSelect={pick}
                        />
                    ))
                )}
            </CategorySelectPopoverShell>
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
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const tree = useMemo(() => buildSelectOptionTree(options), [options]);
    const filteredTree = useMemo(
        () => filterSelectOptionTree(tree, searchQuery),
        [tree, searchQuery],
    );

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

    const { expandedIds, toggleExpanded } = useCategoryTreeExpansion(
        open,
        tree,
        filteredTree,
        searchQuery,
        selectedCategoryId,
    );

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
            <CategorySelectPopoverShell
                open={open}
                searchQuery={searchQuery}
                onSearchQueryChange={setSearchQuery}
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
                {filteredTree.length === 0 ? (
                    <p className="text-muted-foreground px-2 py-3 text-center text-sm">
                        {t('categories.search_no_results')}
                    </p>
                ) : (
                    filteredTree.map((node) => (
                        <CategoryTreeRow
                            key={node.id}
                            node={node}
                            depth={0}
                            value={selectedCategoryId}
                            expandedIds={expandedIds}
                            onToggleExpanded={toggleExpanded}
                            onSelect={pickCategory}
                        />
                    ))
                )}
            </CategorySelectPopoverShell>
        </Popover>
    );
}

export { NONE_VALUE as CATEGORY_SELECT_NONE, UNCATEGORIZED_VALUE as CATEGORY_FILTER_UNCATEGORIZED };
