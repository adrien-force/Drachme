import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';

import { CategoryIndexTree } from '@/components/categories/category-index-tree';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { ColorPicker } from '@/components/ui/color-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { buildCategoryTree, flattenCategoryTree } from '@/lib/category-tree';
import type { CategoriesIndexPageProps, CategoryRecord } from '@/types/category.types';

type FormMode =
    | { type: 'create'; parentId: number | null }
    | { type: 'edit'; category: CategoryRecord };

const DEFAULT_CATEGORY_COLOR = '#94a3b8';

function defaultColorForParent(
    categories: CategoryRecord[],
    parentId: number | null,
): string {
    if (parentId === null) {
        return DEFAULT_CATEGORY_COLOR;
    }

    const parent = categories.find((category) => category.id === parentId);

    return parent?.color ?? DEFAULT_CATEGORY_COLOR;
}

export default function CategoriesIndex({ categories }: CategoriesIndexPageProps) {
    const { t } = useTranslation();
    const [formMode, setFormMode] = useState<FormMode | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<CategoryRecord | null>(null);

    const tree = useMemo(() => buildCategoryTree(categories), [categories]);
    const flatTree = useMemo(() => flattenCategoryTree(tree), [tree]);

    const form = useForm({
        name: '',
        parent_id: '' as string,
        color: DEFAULT_CATEGORY_COLOR,
    });

    const deleteForm = useForm({
        merge_into_category_id: '',
    });

    const openCreate = (parentId: number | null) => {
        form.clearErrors();
        form.setData({
            name: '',
            parent_id: parentId !== null ? String(parentId) : '',
            color: defaultColorForParent(categories, parentId),
        });
        setFormMode({ type: 'create', parentId });
    };

    const openEdit = (category: CategoryRecord) => {
        form.clearErrors();
        form.setData({
            name: category.name,
            parent_id: category.parent_id !== null ? String(category.parent_id) : '',
            color: category.color ?? DEFAULT_CATEGORY_COLOR,
        });
        setFormMode({ type: 'edit', category });
    };

    const submitForm = () => {
        const parentId =
            formMode?.type === 'create'
                ? formMode.parentId
                : form.data.parent_id === ''
                  ? null
                  : Number(form.data.parent_id);

        const payload = {
            name: form.data.name,
            parent_id: parentId,
            color: form.data.color,
        };

        if (formMode?.type === 'edit') {
            form.transform(() => payload);
            form.put(`/categories/${formMode.category.id}`, {
                preserveScroll: true,
                onSuccess: () => setFormMode(null),
            });

            return;
        }

        form.transform(() => payload);
        form.post('/categories', {
            preserveScroll: true,
            onSuccess: () => setFormMode(null),
        });
    };

    const submitDelete = () => {
        if (!deleteTarget) {
            return;
        }

        deleteForm.delete(`/categories/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    };

    const parentOptions = flatTree.filter((node) => {
        if (formMode?.type === 'edit' && node.id === formMode.category.id) {
            return false;
        }

        return node.depth < 3;
    });

    const mergeOptions = categories.filter(
        (category) => deleteTarget && category.id !== deleteTarget.id,
    );

    return (
        <>
            <Head title={t('categories.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {t('categories.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('categories.description')}
                        </p>
                    </div>
                    <Button type="button" onClick={() => openCreate(null)}>
                        <Plus className="mr-2 size-4" />
                        {t('categories.add_root')}
                    </Button>
                </div>

                {tree.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('categories.empty')}
                            </p>
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <FadeIn>
                        <GlassPanel className="p-0">
                            <CategoryIndexTree
                                tree={tree}
                                onAddChild={(parentId) => openCreate(parentId)}
                                onEdit={openEdit}
                                onDelete={(node) => {
                                    deleteForm.setData({
                                        merge_into_category_id: '',
                                    });
                                    setDeleteTarget(node);
                                }}
                            />
                        </GlassPanel>
                    </FadeIn>
                )}
            </div>

            <Dialog open={formMode !== null} onOpenChange={(open) => !open && setFormMode(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {formMode?.type === 'edit'
                                ? t('categories.edit')
                                : formMode?.parentId !== null
                                  ? t('categories.add_child')
                                  : t('categories.add_root')}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="category-name">{t('categories.name')}</Label>
                            <Input
                                id="category-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        {formMode?.type === 'edit' ? (
                            <div className="space-y-2">
                                <Label>{t('categories.parent')}</Label>
                                <Select
                                    value={form.data.parent_id}
                                    onValueChange={(value) =>
                                        form.setData('parent_id', value === 'root' ? '' : value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="root">
                                            {t('categories.parent_none')}
                                        </SelectItem>
                                        {parentOptions.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {'—'.repeat(option.depth - 1)}
                                                {option.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.parent_id} />
                            </div>
                        ) : null}
                        <div className="space-y-2">
                            <Label htmlFor="category-color">{t('categories.color')}</Label>
                            <ColorPicker
                                id="category-color"
                                value={form.data.color}
                                onChange={(color) => form.setData('color', color)}
                            />
                        </div>
                        {'category' in form.errors ? (
                            <InputError
                                message={
                                    (form.errors as Record<string, string>).category
                                }
                            />
                        ) : null}
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setFormMode(null)}
                        >
                            {t('categories.cancel')}
                        </Button>
                        <Button
                            type="button"
                            disabled={form.processing}
                            onClick={submitForm}
                        >
                            {t('categories.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleteTarget !== null}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {t('categories.delete_confirm', {
                                name: deleteTarget?.name ?? '',
                            })}
                        </DialogTitle>
                        {deleteTarget && deleteTarget.transactions_count > 0 ? (
                            <DialogDescription>
                                {t('categories.merge_hint')}
                            </DialogDescription>
                        ) : null}
                    </DialogHeader>
                    {deleteTarget && deleteTarget.transactions_count > 0 ? (
                        <div className="space-y-2">
                            <Label>{t('categories.merge_into')}</Label>
                            <Select
                                value={deleteForm.data.merge_into_category_id}
                                onValueChange={(value) =>
                                    deleteForm.setData('merge_into_category_id', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {mergeOptions.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={String(option.id)}
                                        >
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={deleteForm.errors.merge_into_category_id}
                            />
                        </div>
                    ) : null}
                    {'category' in deleteForm.errors ? (
                        <InputError
                            message={
                                (deleteForm.errors as Record<string, string>).category
                            }
                        />
                    ) : null}
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleteTarget(null)}
                        >
                            {t('categories.cancel')}
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={deleteForm.processing}
                            onClick={submitDelete}
                        >
                            {t('categories.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [
        { titleKey: 'nav.configuration', href: '/categories' },
        { titleKey: 'nav.categories', href: '/categories' },
    ],
};
