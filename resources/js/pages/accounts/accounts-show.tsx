import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil, Upload } from 'lucide-react';

import { AccountTypeBadge } from '@/components/accounts/account-type-badge';
import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency } from '@/lib/format-currency';
import type { AccountsShowPageProps } from '@/types/account.types';

export default function AccountsShow({ account, transactions }: AccountsShowPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={account.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4">
                    <Button asChild variant="ghost" size="sm" className="w-fit px-2">
                        <Link href="/accounts">
                            <ArrowLeft className="mr-2 size-4" />
                            {t('accounts.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex flex-wrap items-center gap-3">
                                <EntityLogo
                                    name={account.name}
                                    logoUrl={account.logo_url}
                                    className="size-12"
                                />
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    {account.name}
                                </h1>
                                <AccountTypeBadge type={account.type} />
                                {account.is_archived && (
                                    <span className="text-muted-foreground text-sm">
                                        {t('accounts.archived_label')}
                                    </span>
                                )}
                            </div>
                            {account.institution && (
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {account.institution}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href="/import">
                                    <Upload className="mr-2 size-4" />
                                    {t('accounts.import')}
                                </Link>
                            </Button>
                            <Button asChild size="sm">
                                <Link href={`/accounts/${account.id}/edit`}>
                                    <Pencil className="mr-2 size-4" />
                                    {t('accounts.edit')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <FadeIn>
                    <GlassPanel className="p-6">
                        <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                            {t('accounts.current_balance')}
                        </p>
                        <p className="mt-2 text-3xl font-semibold tabular-nums">
                            {formatCurrency(account.current_balance, { precise: true })}
                        </p>
                    </GlassPanel>
                </FadeIn>

                <FadeIn delay={0.05}>
                    <GlassPanel className="p-6">
                        <h2 className="text-lg font-semibold">
                            {t('accounts.recent_transactions')}
                        </h2>
                        {transactions.length === 0 ? (
                            <p className="text-muted-foreground mt-4 text-sm">
                                {t('accounts.no_transactions')}
                            </p>
                        ) : null}
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

AccountsShow.layout = {
    breadcrumbs: [
        { title: 'Comptes', href: '/accounts' },
    ],
};
