import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { LogoUploadField } from '@/components/logo-upload-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type ProfilePageProps = {
    mustVerifyEmail: boolean;
    status?: string;
    profile: {
        avatar_url: string | null;
        month_start_day: number;
    };
};

export default function Profile({
    mustVerifyEmail,
    status,
    profile,
}: ProfilePageProps) {
    const { auth } = usePage().props;
    const { t } = useTranslation();
    const userLocale =
        typeof auth.user.locale === 'string' ? auth.user.locale : 'fr';
    const [locale, setLocale] = useState(userLocale);
    const [monthStartDay, setMonthStartDay] = useState(
        String(profile.month_start_day),
    );

    return (
        <>
            <Head title={t('settings.profile')} />

            <h1 className="sr-only">{t('settings.profile')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('settings.profile_title')}
                    description={t('settings.profile_description')}
                />

                <Form
                    {...ProfileController.update.form()}
                    encType="multipart/form-data"
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <LogoUploadField
                                name={auth.user.name}
                                currentLogoUrl={profile.avatar_url}
                                disabled={processing}
                                fieldName="avatar"
                                removeFieldName="remove_avatar"
                                label={t('settings.profile_photo')}
                                hint={t('settings.profile_photo_hint')}
                            />
                            <InputError message={errors.avatar} />

                            <div className="grid gap-2">
                                <Label htmlFor="name">{t('settings.name')}</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder={t('settings.name_placeholder')}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">{t('settings.email')}</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder={t('settings.email_placeholder')}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="locale">{t('settings.language')}</Label>
                                <input type="hidden" name="locale" value={locale} />
                                <Select value={locale} onValueChange={setLocale}>
                                    <SelectTrigger id="locale" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fr">
                                            {t('locales.fr')}
                                        </SelectItem>
                                        <SelectItem value="en">
                                            {t('locales.en')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-muted-foreground text-sm">
                                    {t('settings.language_hint')}
                                </p>
                                <InputError
                                    className="mt-2"
                                    message={errors.locale}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="month_start_day">
                                    {t('settings.month_start_day')}
                                </Label>
                                <Input
                                    id="month_start_day"
                                    name="month_start_day"
                                    type="number"
                                    min={1}
                                    max={31}
                                    step={1}
                                    value={monthStartDay}
                                    onChange={(event) =>
                                        setMonthStartDay(event.target.value)
                                    }
                                    required
                                />
                                <p className="text-muted-foreground text-sm leading-relaxed">
                                    {t('settings.month_start_day_hint')}
                                </p>
                                <InputError message={errors.month_start_day} />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            {t('settings.email_unverified')}{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                {t('settings.resend_verification')}
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                {t('settings.verification_sent')}
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    {t('settings.save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
