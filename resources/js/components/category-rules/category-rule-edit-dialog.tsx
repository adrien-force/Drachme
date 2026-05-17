import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import { CategorySelect } from '@/components/categories/category-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import type { CategorySelectOption } from '@/types/category.types';
import type { CategoryRuleRecord } from '@/types/category-rule.types';

type Props = {
    rule: CategoryRuleRecord | null;
    categoryOptions: CategorySelectOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function CategoryRuleEditDialog({
    rule,
    categoryOptions,
    open,
    onOpenChange,
}: Props) {
    const { t } = useTranslation();

    const form = useForm({
        pattern: '',
        category_id: null as number | null,
        priority: 0,
        is_active: true,
    });

    useEffect(() => {
        if (rule === null || !open) {
            return;
        }

        form.setData({
            pattern: rule.pattern,
            category_id: rule.category_id,
            priority: rule.priority,
            is_active: rule.is_active,
        });
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps -- reset when rule or dialog opens
    }, [rule, open]);

    const submit = () => {
        if (rule === null || form.data.category_id === null) {
            return;
        }

        form.put(`/category-rules/${rule.id}`, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('category_rules.edit_rule')}</DialogTitle>
                </DialogHeader>

                <div className="grid gap-4 py-2">
                    <div className="space-y-2">
                        <Label htmlFor="edit-rule-pattern">
                            {t('category_rules.pattern')}
                        </Label>
                        <Input
                            id="edit-rule-pattern"
                            value={form.data.pattern}
                            onChange={(event) =>
                                form.setData('pattern', event.target.value)
                            }
                        />
                        <InputError message={form.errors.pattern} />
                    </div>

                    <div className="space-y-2">
                        <Label>{t('category_rules.target_category')}</Label>
                        <CategorySelect
                            value={form.data.category_id}
                            onChange={(id) => form.setData('category_id', id)}
                            options={categoryOptions}
                            noneLabel={t('category_rules.choose_category')}
                        />
                        <InputError message={form.errors.category_id} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edit-rule-priority">
                            {t('category_rules.priority')}
                        </Label>
                        <Input
                            id="edit-rule-priority"
                            type="number"
                            min={0}
                            value={String(form.data.priority)}
                            onChange={(event) =>
                                form.setData(
                                    'priority',
                                    Number.parseInt(event.target.value, 10) || 0,
                                )
                            }
                        />
                        <InputError message={form.errors.priority} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="edit-rule-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                        />
                        <Label
                            htmlFor="edit-rule-active"
                            className="cursor-pointer font-normal"
                        >
                            {t('category_rules.active')}
                        </Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        {t('category_rules.cancel')}
                    </Button>
                    <Button
                        type="button"
                        disabled={form.processing || form.data.category_id === null}
                        onClick={submit}
                    >
                        {t('category_rules.save')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
