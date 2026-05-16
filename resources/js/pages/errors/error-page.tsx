import { Head, Link } from '@inertiajs/react';
import { Home, ShieldAlert } from 'lucide-react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

type ErrorPageProps = {
    status: number;
};

export default function ErrorPage({ status }: ErrorPageProps) {
    const { t } = useTranslation();
    const isForbidden = Number(status) === 403;

    const titleKey = isForbidden ? 'errors.forbidden_title' : 'errors.not_found_title';
    const descriptionKey = isForbidden
        ? 'errors.forbidden_description'
        : 'errors.not_found_description';

    return (
        <>
            <Head title={t(titleKey)} />

            <FadeIn className="flex min-h-[60vh] flex-1 items-center justify-center p-4 md:p-6">
                <GlassPanel className="w-full max-w-lg p-8 text-center">
                    {isForbidden ? (
                        <ShieldAlert
                            className="text-destructive mx-auto mb-4 size-12"
                            strokeWidth={1.75}
                        />
                    ) : (
                        <span className="text-primary block text-5xl font-semibold tabular-nums">
                            404
                        </span>
                    )}
                    <h1 className="mt-4 text-xl font-semibold tracking-tight">
                        {t(titleKey)}
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                        {t(descriptionKey)}
                    </p>
                    <Button asChild className="mt-6">
                        <Link href={dashboard()}>
                            <Home className="mr-2 size-4" />
                            {t('shell.back_to_dashboard')}
                        </Link>
                    </Button>
                </GlassPanel>
            </FadeIn>
        </>
    );
}
