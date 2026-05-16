import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import InputError from '@/components/input-error';
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
import type { AccountType, AccountsFormPageProps } from '@/types/account.types';

export default function AccountsForm({
    account,
    accountTypes,
}: AccountsFormPageProps) {
    const { t } = useTranslation();
    const isEditing = account !== null;
    const [type, setType] = useState<AccountType>(account?.type ?? 'checking');

    return (
        <>
            <Head
                title={
                    isEditing ? t('accounts.edit_title') : t('accounts.create_title')
                }
            />

            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {isEditing ? t('accounts.edit_title') : t('accounts.create_title')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {isEditing
                            ? t('accounts.edit_description')
                            : t('accounts.create_description')}
                    </p>
                </div>

                <FadeIn>
                    <GlassPanel className="p-6">
                        <Form
                            action={
                                isEditing ? `/accounts/${account.id}` : '/accounts'
                            }
                            method={isEditing ? 'put' : 'post'}
                            className="space-y-6"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input type="hidden" name="type" value={type} />

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">{t('accounts.name')}</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={account?.name ?? ''}
                                            required
                                            placeholder={t('accounts.name_placeholder')}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="institution">
                                            {t('accounts.institution')}
                                        </Label>
                                        <Input
                                            id="institution"
                                            name="institution"
                                            defaultValue={account?.institution ?? ''}
                                            placeholder={t(
                                                'accounts.institution_placeholder',
                                            )}
                                        />
                                        <InputError message={errors.institution} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="type">{t('accounts.type')}</Label>
                                        <Select
                                            value={type}
                                            onValueChange={(value) =>
                                                setType(value as AccountType)
                                            }
                                        >
                                            <SelectTrigger id="type" className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
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
                                        <InputError message={errors.type} />
                                    </div>

                                    {!isEditing && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="initial_balance">
                                                {t('accounts.initial_balance')}
                                            </Label>
                                            <Input
                                                id="initial_balance"
                                                name="initial_balance"
                                                type="number"
                                                step="0.01"
                                                defaultValue="0"
                                                required
                                            />
                                            <InputError message={errors.initial_balance} />
                                        </div>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="opened_at">
                                            {t('accounts.opened_at')}
                                        </Label>
                                        <Input
                                            id="opened_at"
                                            name="opened_at"
                                            type="date"
                                            defaultValue={account?.opened_at ?? ''}
                                        />
                                        <InputError message={errors.opened_at} />
                                    </div>

                                    <div className="flex flex-wrap gap-3">
                                        <Button type="submit" disabled={processing}>
                                            {isEditing
                                                ? t('accounts.save')
                                                : t('accounts.create')}
                                        </Button>
                                        <Button type="button" variant="outline" asChild>
                                            <Link href="/accounts">
                                                {t('accounts.cancel')}
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </GlassPanel>
                </FadeIn>
            </div>
        </>
    );
}

AccountsForm.layout = {
    breadcrumbs: [
        {
            title: 'Comptes',
            href: '/accounts',
        },
    ],
};
