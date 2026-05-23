import { Head, router, useForm } from '@inertiajs/react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { TransactionFormPanel } from '@/components/transactions/transaction-form-panel';
import { useTranslation } from '@/hooks/use-translation';
import type { TransactionsFormPageProps } from '@/types/transaction.types';

export default function TransactionsForm({
    accounts,
    presetAccountId,
    typeOptions,
    categoryOptions,
    suggestedCategory,
}: TransactionsFormPageProps) {
    const { t } = useTranslation();

    const form = useForm({
        account_id: String(presetAccountId ?? accounts[0]?.id ?? ''),
        date: new Date().toISOString().slice(0, 10),
        label: '',
        amount: '',
        type: 'auto',
        notes: '',
        category_id: null as number | null,
        apply_category_rules: true,
        is_card_settlement: false,
        card_period_start: null as string | null,
    });

    const submit = () => {
        const payload = {
            ...form.data,
            account_id: Number(form.data.account_id),
            type: form.data.type === 'auto' ? null : form.data.type,
            category_id: form.data.category_id,
            apply_category_rules: form.data.apply_category_rules,
        };

        form.transform(() => payload);
        form.post('/transactions', { preserveScroll: true });
    };

    const cancel = () => {
        const accountId = Number(form.data.account_id);
        if (accountId > 0) {
            router.visit(`/accounts/${accountId}`);

            return;
        }

        router.visit('/accounts');
    };

    return (
        <>
            <Head title={t('transactions.create_title')} />

            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('transactions.create_title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('transactions.create_description')}
                    </p>
                </div>

                <FadeIn>
                    <GlassPanel className="p-6">
                        <TransactionFormPanel
                            form={form}
                            accounts={accounts}
                            typeOptions={typeOptions}
                            categoryOptions={categoryOptions}
                            suggestedCategory={suggestedCategory}
                            transaction={null}
                            isEditing={false}
                            onCancel={cancel}
                            onSubmit={submit}
                        />
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

TransactionsForm.layout = {
    breadcrumbs: [{ titleKey: 'nav.transactions', href: '/transactions' }],
};
