import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';

import { AccountTypeBadge } from '@/components/accounts/account-type-badge';
import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { accountDisplayBalance } from '@/lib/account-display-balance';
import { formatCurrency } from '@/lib/format-currency';
import { cn } from '@/lib/utils';
import type { AccountType, AccountsIndexPageProps } from '@/types/account.types';

const ALL_TYPES = 'all' as const;

function formatLastActivity(iso: string | null, locale: string): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'fr-FR', {
        dateStyle: 'medium',
    }).format(new Date(`${iso}T12:00:00`));
}

export default function AccountsIndex({
    accounts,
    filters,
    accountTypes,
}: AccountsIndexPageProps) {
    const { t, locale } = useTranslation();
    const [typeFilter, setTypeFilter] = useState<AccountType | typeof ALL_TYPES>(
        ALL_TYPES,
    );

    const filteredAccounts = useMemo(() => {
        if (typeFilter === ALL_TYPES) {
            return accounts;
        }

        return accounts.filter((account) => account.type === typeFilter);
    }, [accounts, typeFilter]);

    const toggleArchived = (checked: boolean) => {
        router.get(
            '/accounts',
            { archived: checked ? '1' : undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
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

                <FadeIn>
                    <GlassPanel className="flex flex-col gap-4 p-4 sm:flex-row sm:items-end sm:justify-between">
                        <div className="flex flex-col gap-2 sm:max-w-xs">
                            <Label htmlFor="account-type-filter">
                                {t('accounts.filter_type')}
                            </Label>
                            <Select
                                value={typeFilter}
                                onValueChange={(value) =>
                                    setTypeFilter(value as AccountType | typeof ALL_TYPES)
                                }
                            >
                                <SelectTrigger id="account-type-filter">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL_TYPES}>
                                        {t('accounts.filter_all_types')}
                                    </SelectItem>
                                    {accountTypes.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="show-archived"
                                checked={filters.archived}
                                onCheckedChange={(checked) =>
                                    toggleArchived(checked === true)
                                }
                            />
                            <Label htmlFor="show-archived" className="cursor-pointer">
                                {t('accounts.show_archived')}
                            </Label>
                        </div>
                    </GlassPanel>
                </FadeIn>

                {filteredAccounts.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('accounts.empty')}
                            </p>
                            {!filters.archived && (
                                <Button asChild className="mt-4" variant="outline">
                                    <Link href="/accounts/create">
                                        {t('accounts.create_first')}
                                    </Link>
                                </Button>
                            )}
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <FadeIn>
                        <GlassPanel className="overflow-x-auto p-0">
                            <table className="w-full min-w-[640px] text-sm">
                                <thead>
                                    <tr className="border-border/60 text-muted-foreground border-b text-left text-xs uppercase tracking-wide">
                                        <th className="px-4 py-3 font-medium">
                                            {t('accounts.name')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('accounts.institution')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('accounts.type')}
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            {t('accounts.balance_column')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('accounts.last_activity')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredAccounts.map((account) => (
                                        <tr
                                            key={account.id}
                                            className={cn(
                                                'border-border/40 hover:bg-muted/30 border-b transition-colors last:border-0',
                                                account.is_archived && 'opacity-60',
                                            )}
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/accounts/${account.id}`}
                                                    className="flex items-center gap-3 font-medium hover:underline"
                                                >
                                                    <EntityLogo
                                                        name={account.name}
                                                        logoUrl={account.logo_url}
                                                    />
                                                    <span>{account.name}</span>
                                                </Link>
                                                {account.is_archived && (
                                                    <span className="text-muted-foreground ml-2 text-xs">
                                                        ({t('accounts.archived_label')})
                                                    </span>
                                                )}
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3">
                                                {account.institution ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <AccountTypeBadge type={account.type} />
                                            </td>
                                            <td className="px-4 py-3 text-right font-medium tabular-nums">
                                                {account.type === 'credit_card' ? (
                                                    <div className="flex flex-col items-end gap-0.5">
                                                        <span>
                                                            {formatCurrency(
                                                                accountDisplayBalance(account),
                                                                { precise: true },
                                                            )}
                                                        </span>
                                                        <span className="text-muted-foreground text-xs font-normal">
                                                            {t('accounts.current_period_short')}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    formatCurrency(account.current_balance, {
                                                        precise: true,
                                                    })
                                                )}
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3 tabular-nums">
                                                {formatLastActivity(
                                                    account.last_activity_at,
                                                    locale,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </GlassPanel>
                    </FadeIn>
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
