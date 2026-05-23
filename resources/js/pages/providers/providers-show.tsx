import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil, Upload } from 'lucide-react';

import { EntityLogo } from '@/components/entity-logo';
import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { ProviderSetupSummary } from '@/components/providers/provider-setup-summary';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import type { ProvidersShowPageProps } from '@/types/provider.types';

export default function ProvidersShow({
    provider,
    fieldOptions,
    positionFieldOptions,
}: ProvidersShowPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={provider.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4">
                    <Button asChild variant="ghost" size="sm" className="w-fit px-2">
                        <Link href="/providers">
                            <ArrowLeft className="mr-2 size-4" />
                            {t('providers.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-center gap-3">
                            <EntityLogo
                                name={provider.name}
                                logoUrl={provider.logo_url}
                                className="size-12"
                            />
                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    {provider.name}
                                </h1>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {t('providers.show_description')}
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href="/import">
                                    <Upload className="mr-2 size-4" />
                                    {t('providers.use_for_import')}
                                </Link>
                            </Button>
                            <Button asChild size="sm">
                                <Link href={`/providers/${provider.id}/edit`}>
                                    <Pencil className="mr-2 size-4" />
                                    {t('providers.edit')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <FadeIn>
                    <GlassPanel className="space-y-6 p-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground text-xs uppercase tracking-wide">
                                    {t('providers.import_type_label')}
                                </dt>
                                <dd className="mt-1 text-sm font-medium">
                                    {provider.import_type === 'positions'
                                        ? t('providers.import_type_positions')
                                        : t('providers.import_type_transactions')}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs uppercase tracking-wide">
                                    {t('providers.default_account')}
                                </dt>
                                <dd className="mt-1 text-sm font-medium">
                                    {provider.default_account_name ??
                                        t('providers.default_account_none')}
                                </dd>
                            </div>
                        </dl>

                        <ProviderSetupSummary
                            provider={provider}
                            fieldOptions={
                                provider.import_type === 'positions'
                                    ? positionFieldOptions
                                    : fieldOptions
                            }
                        />
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

ProvidersShow.layout = {
    breadcrumbs: [
        { titleKey: 'nav.providers', href: '/providers' },
    ],
};
