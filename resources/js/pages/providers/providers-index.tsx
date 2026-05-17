import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';

import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import type { ProvidersIndexPageProps } from '@/types/provider.types';

function formatUpdatedAt(iso: string | null, locale: string): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

export default function ProvidersIndex({ providers }: ProvidersIndexPageProps) {
    const { t, locale } = useTranslation();

    const handleDelete = (id: number, name: string) => {
        if (!window.confirm(t('providers.delete_confirm', { name }))) {
            return;
        }

        router.delete(`/providers/${id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('providers.title')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {t('providers.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('providers.description')}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/providers/create">
                            <Plus className="mr-2 size-4" />
                            {t('providers.create')}
                        </Link>
                    </Button>
                </div>

                {providers.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('providers.empty')}
                            </p>
                            <Button asChild className="mt-4" variant="outline">
                                <Link href="/providers/create">{t('providers.create')}</Link>
                            </Button>
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <FadeIn>
                        <GlassPanel className="overflow-x-auto p-0">
                            <table className="w-full min-w-[640px] text-sm">
                                <thead>
                                    <tr className="border-border/60 text-muted-foreground border-b text-left text-xs uppercase tracking-wide">
                                        <th className="px-4 py-3 font-medium">
                                            {t('providers.name')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('providers.default_account')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('providers.updated_at')}
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            {t('providers.actions')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {providers.map((provider) => (
                                        <tr
                                            key={provider.id}
                                            className="border-border/40 hover:bg-muted/30 border-b last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3 font-medium">
                                                    <EntityLogo
                                                        name={provider.name}
                                                        logoUrl={provider.logo_url}
                                                    />
                                                    <span>{provider.name}</span>
                                                </div>
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3">
                                                {provider.default_account_name ?? '—'}
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3">
                                                {formatUpdatedAt(provider.updated_at, locale)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        <Link
                                                            href={`/providers/${provider.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        <Link
                                                            href={`/providers/${provider.id}/edit`}
                                                        >
                                                            <Pencil className="size-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleDelete(
                                                                provider.id,
                                                                provider.name,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
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

ProvidersIndex.layout = {
    breadcrumbs: [
        { title: 'Paramétrage', href: '/categories' },
        { title: 'Fournisseurs', href: '/providers' },
    ],
};
