import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { AccountsIndexPageProps } from '@/types/account.types';

export default function AccountsIndex({ accounts }: AccountsIndexPageProps) {
    const { t } = useTranslation();

    const handleArchive = (accountId: number, name: string) => {
        if (!window.confirm(t('accounts.archive_confirm', { name }))) {
            return;
        }

        router.delete(`/accounts/${accountId}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('accounts.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {t('accounts.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('accounts.description')}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/accounts/create">
                            <Plus className="mr-2 size-4" />
                            {t('accounts.create')}
                        </Link>
                    </Button>
                </div>

                {accounts.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('accounts.empty')}
                            </p>
                            <Button asChild className="mt-4" variant="outline">
                                <Link href="/accounts/create">
                                    {t('accounts.create_first')}
                                </Link>
                            </Button>
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {accounts.map((account, index) => (
                            <FadeIn key={account.id} delay={index * 0.05}>
                                <GlassPanel className="flex flex-col gap-4 p-5">
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                                            {t(`accounts.types.${account.type}`)}
                                        </p>
                                        <h2 className="mt-1 text-lg font-semibold">
                                            {account.name}
                                        </h2>
                                        {account.institution && (
                                            <p className="text-muted-foreground text-sm">
                                                {account.institution}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground text-xs">
                                            {t('accounts.current_balance')}
                                        </p>
                                        <p className="text-xl font-semibold tabular-nums">
                                            {formatCurrency(account.current_balance, {
                                                precise: true,
                                            })}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="flex-1"
                                        >
                                            <Link href={`/accounts/${account.id}/edit`}>
                                                <Pencil className="mr-2 size-4" />
                                                {t('accounts.edit')}
                                            </Link>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                handleArchive(account.id, account.name)
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </GlassPanel>
                            </FadeIn>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AccountsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Comptes',
            href: '/accounts',
        },
    ],
};
