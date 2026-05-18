import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { TransactionTriagePanel } from '@/components/transactions/transaction-triage-panel';
import { FadeIn } from '@/components/motion/fade-in';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import type { TransactionsTriagePageProps } from '@/types/transaction-triage.types';

export default function TransactionsTriage(props: TransactionsTriagePageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('transactions.triage.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <FadeIn>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <Button asChild variant="ghost" size="sm" className="mb-2 w-fit px-2">
                                <Link href="/transactions">
                                    <ArrowLeft className="mr-2 size-4" />
                                    {t('transactions.triage.back_to_list')}
                                </Link>
                            </Button>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {t('transactions.triage.title')}
                            </h1>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {t('transactions.triage.description')}
                            </p>
                        </div>
                    </div>
                </FadeIn>

                <TransactionTriagePanel {...props} />
            </div>
        </>
    );
}

TransactionsTriage.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: '/transactions' },
    ],
};
