import { Head } from '@inertiajs/react';

import AppearanceToggleTab from '@/components/appearance-tabs';
import { ThemeColorsForm } from '@/components/settings/theme-colors-form';
import Heading from '@/components/heading';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('settings.appearance')} />

            <h1 className="sr-only">{t('settings.appearance')}</h1>

            <div className="space-y-8">
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('settings.appearance_mode_title')}
                        description={t('settings.appearance_mode_description')}
                    />
                    <AppearanceToggleTab />
                </div>

                <Separator />

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('settings.colors_title')}
                        description={t('settings.colors_description')}
                    />
                    <ThemeColorsForm />
                </div>
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};
