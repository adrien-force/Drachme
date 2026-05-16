import { Head, Link } from '@inertiajs/react';
import { Construction } from 'lucide-react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

export type PlaceholderPageProps = {
    titleKey: string;
    descriptionKey: string;
};

export default function PlaceholderPage({
    titleKey,
    descriptionKey,
}: PlaceholderPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t(titleKey)} />

            <FadeIn className="flex flex-1 items-center justify-center p-4 md:p-6">
                <GlassPanel className="w-full max-w-lg p-8 text-center">
                    <Construction
                        className="text-primary mx-auto mb-4 size-12"
                        strokeWidth={1.75}
                    />
                    <h2 className="text-xl font-semibold tracking-tight">
                        {t(titleKey)}
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                        {t(descriptionKey)}
                    </p>
                    <Button asChild className="mt-6">
                        <Link href={dashboard()}>{t('shell.back_to_dashboard')}</Link>
                    </Button>
                </GlassPanel>
            </FadeIn>
        </>
    );
}
