import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { CategorySelect } from '@/components/categories/category-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { patternFromTokens, tokenizeLabel } from '@/lib/label-tokenizer';
import { cn } from '@/lib/utils';
import type { CategorySelectOption } from '@/types/category.types';

type CreateRuleFromLabelDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    label: string;
    categoryOptions: CategorySelectOption[];
    applyToTransactionId?: number | null;
};

export function CreateRuleFromLabelDialog({
    open,
    onOpenChange,
    label,
    categoryOptions,
    applyToTransactionId = null,
}: CreateRuleFromLabelDialogProps) {
    const { t } = useTranslation();
    const tokens = useMemo(() => tokenizeLabel(label), [label]);
    const [selectedTokens, setSelectedTokens] = useState<string[]>([]);
    const [categoryId, setCategoryId] = useState<number | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const toggleToken = (token: string) => {
        setSelectedTokens((current) =>
            current.includes(token)
                ? current.filter((item) => item !== token)
                : [...current, token],
        );
    };

    const previewPattern = patternFromTokens(selectedTokens);

    const submit = () => {
        if (categoryId === null || selectedTokens.length === 0) {
            return;
        }

        setIsSubmitting(true);
        router.post(
            '/category-rules/from-label',
            {
                label,
                selected_tokens: selectedTokens,
                category_id: categoryId,
                apply_to_transaction_id: applyToTransactionId,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    setSelectedTokens([]);
                    setCategoryId(null);
                    setErrors({});
                },
                onError: (pageErrors) => {
                    setErrors(pageErrors as Record<string, string>);
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{t('category_rules.from_label_title')}</DialogTitle>
                    <DialogDescription>
                        {t('category_rules.from_label_description')}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('transactions.label')}</Label>
                        <p className="bg-muted/40 rounded-md border px-3 py-2 font-mono text-sm">
                            {label}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label>{t('category_rules.select_tokens')}</Label>
                        <div className="flex flex-wrap gap-2">
                            {tokens.map((token) => {
                                const selected = selectedTokens.includes(token);

                                return (
                                    <button
                                        key={token}
                                        type="button"
                                        onClick={() => toggleToken(token)}
                                        className={cn(
                                            'rounded-full border px-3 py-1 text-sm transition-colors',
                                            selected
                                                ? 'border-primary bg-primary/15 text-primary'
                                                : 'border-border hover:bg-muted/50',
                                        )}
                                    >
                                        {token}
                                    </button>
                                );
                            })}
                        </div>
                        {tokens.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t('category_rules.no_tokens')}
                            </p>
                        ) : null}
                        <InputError message={errors.selected_tokens} />
                    </div>

                    {previewPattern ? (
                        <p className="text-muted-foreground text-sm">
                            {t('category_rules.pattern_preview', {
                                pattern: previewPattern,
                            })}
                        </p>
                    ) : null}

                    <div className="space-y-2">
                        <Label>{t('category_rules.target_category')}</Label>
                        <CategorySelect
                            value={categoryId}
                            onChange={setCategoryId}
                            options={categoryOptions}
                            noneLabel={t('category_rules.choose_category')}
                        />
                        <InputError message={errors.category_id} />
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
                        disabled={
                            selectedTokens.length === 0 ||
                            categoryId === null ||
                            isSubmitting
                        }
                        onClick={submit}
                    >
                        {t('category_rules.create_rule')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
