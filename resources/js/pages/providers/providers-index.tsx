import { Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';

type ProviderListItem = {
    id: number;
    name: string;
    default_account_id: number | null;
    default_account_name: string | null;
};

type ProvidersIndexPageProps = {
    providers: ProviderListItem[];
};

export default function ProvidersIndex({ providers }: ProvidersIndexPageProps) {
    const { t } = useTranslation();

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
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('providers.title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('providers.description')}
                    </p>
                    <p className="text-muted-foreground mt-2 text-xs">
                        {t('providers.wizard_hint')}
                    </p>
                </div>

                {providers.length === 0 ? (
                    <FadeIn>
                        <GlassPanel className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('providers.empty')}
                            </p>
                        </GlassPanel>
                    </FadeIn>
                ) : (
                    <FadeIn>
                        <GlassPanel className="overflow-x-auto p-0">
                            <table className="w-full min-w-[520px] text-sm">
                                <thead>
                                    <tr className="border-border/60 text-muted-foreground border-b text-left text-xs uppercase tracking-wide">
                                        <th className="px-4 py-3 font-medium">
                                            {t('providers.name')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('providers.default_account')}
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
                                            <td className="px-4 py-3 font-medium">
                                                {provider.name}
                                            </td>
                                            <td className="text-muted-foreground px-4 py-3">
                                                {provider.default_account_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right">
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
        {
            title: 'Fournisseurs',
            href: '/providers',
        },
    ],
};
