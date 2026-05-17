import { Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';

import { FadeIn } from '@/components/motion/fade-in';
import { GlassPanel } from '@/components/glass-panel';
import { LogoUploadField } from '@/components/logo-upload-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
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
import { formatCurrency } from '@/lib/format-currency';
import type { AccountType, AccountsFormPageProps } from '@/types/account.types';

export default function AccountsForm({
    account,
    accountTypes,
}: AccountsFormPageProps) {
    const { t } = useTranslation();
    const isEditing = account !== null;
    const formRef = useRef<HTMLFormElement>(null);
    const [type, setType] = useState<AccountType>(account?.type ?? 'checking');
    const [accountName, setAccountName] = useState(account?.name ?? '');
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [openedAt, setOpenedAt] = useState<string | null>(account?.opened_at ?? null);
    const [actualBalance, setActualBalance] = useState(
        account !== null ? String(account.current_balance) : '',
    );
    const [submitting, setSubmitting] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const balanceReconcile = useMemo(() => {
        if (account === null) {
            return null;
        }

        const computedBalance = account.current_balance;
        const initialBalance = account.initial_balance;
        const parsedActual = Number.parseFloat(actualBalance);

        if (Number.isNaN(parsedActual)) {
            return {
                computedBalance,
                initialBalance,
                adjustment: 0,
                newInitialBalance: initialBalance,
            };
        }

        const adjustment = Math.round((parsedActual - computedBalance) * 100) / 100;
        const newInitialBalance =
            Math.round((initialBalance + adjustment) * 100) / 100;

        return {
            computedBalance,
            initialBalance,
            adjustment,
            newInitialBalance,
        };
    }, [account, actualBalance]);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const form = formRef.current;
        if (!form) {
            return;
        }

        const formData = new FormData(form);
        const payload = new FormData();

        payload.append('name', accountName);
        payload.append('type', type);

        const institution = formData.get('institution');
        if (typeof institution === 'string' && institution !== '') {
            payload.append('institution', institution);
        }

        if (openedAt !== null && openedAt !== '') {
            payload.append('opened_at', openedAt);
        }

        if (!isEditing) {
            const initialBalance = formData.get('initial_balance');
            if (typeof initialBalance === 'string') {
                payload.append('initial_balance', initialBalance);
            }
        } else if (actualBalance !== '') {
            payload.append('actual_balance', actualBalance);
        }

        if (logoFile) {
            payload.append('logo', logoFile);
        }

        if (removeLogo) {
            payload.append('remove_logo', '1');
        }

        setSubmitting(true);
        setErrors({});

        const options = {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => setSubmitting(false),
            onError: (pageErrors: Record<string, string>) => setErrors(pageErrors),
        };

        if (isEditing && account) {
            router.put(`/accounts/${account.id}`, payload, options);

            return;
        }

        router.post('/accounts', payload, options);
    };

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
                        <form
                            ref={formRef}
                            onSubmit={handleSubmit}
                            className="space-y-6"
                        >
                            <LogoUploadField
                                name={accountName}
                                currentLogoUrl={account?.logo_url}
                                disabled={submitting}
                                useNativeFormFields={false}
                                onFileChange={setLogoFile}
                                onRemoveChange={setRemoveLogo}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="name">{t('accounts.name')}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    value={accountName}
                                    onChange={(event) =>
                                        setAccountName(event.target.value)
                                    }
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
                                    placeholder={t('accounts.institution_placeholder')}
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


                            {!isEditing ? (
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
                            ) : null}

                            {isEditing && account && balanceReconcile ? (
                                <div className="space-y-4 rounded-lg border border-white/10 bg-white/5 p-4">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {t('accounts.balance_reconcile_title')}
                                        </p>
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            {t('accounts.balance_reconcile_hint')}
                                        </p>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="space-y-1">
                                            <p className="text-muted-foreground text-xs">
                                                {t('accounts.initial_balance')}
                                            </p>
                                            <p className="font-mono text-sm tabular-nums">
                                                {formatCurrency(
                                                    balanceReconcile.initialBalance,
                                                    { precise: true },
                                                )}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-muted-foreground text-xs">
                                                {t('accounts.computed_balance')}
                                            </p>
                                            <p className="font-mono text-sm tabular-nums">
                                                {formatCurrency(
                                                    balanceReconcile.computedBalance,
                                                    { precise: true },
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="actual_balance">
                                            {t('accounts.actual_balance')}
                                        </Label>
                                        <Input
                                            id="actual_balance"
                                            name="actual_balance"
                                            type="number"
                                            step="0.01"
                                            value={actualBalance}
                                            onChange={(event) =>
                                                setActualBalance(event.target.value)
                                            }
                                        />
                                        <InputError message={errors.actual_balance} />
                                    </div>

                                    {actualBalance !== '' &&
                                    !Number.isNaN(Number.parseFloat(actualBalance)) ? (
                                        <div className="grid gap-2 text-sm sm:grid-cols-2">
                                            <div className="rounded-md border border-white/10 px-3 py-2">
                                                <p className="text-muted-foreground text-xs">
                                                    {t('accounts.balance_adjustment')}
                                                </p>
                                                <p className="mt-1 font-mono tabular-nums">
                                                    {balanceReconcile.adjustment >= 0
                                                        ? '+'
                                                        : ''}
                                                    {formatCurrency(
                                                        balanceReconcile.adjustment,
                                                        { precise: true },
                                                    )}
                                                </p>
                                            </div>
                                            <div className="rounded-md border border-white/10 px-3 py-2">
                                                <p className="text-muted-foreground text-xs">
                                                    {t('accounts.initial_balance_after')}
                                                </p>
                                                <p className="mt-1 font-mono tabular-nums">
                                                    {formatCurrency(
                                                        balanceReconcile.newInitialBalance,
                                                        { precise: true },
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    ) : null}
                                </div>
                            ) : null}

                            <div className="grid gap-2">
                                <Label htmlFor="opened_at">{t('accounts.opened_at')}</Label>
                                <DatePicker
                                    id="opened_at"
                                    value={openedAt}
                                    clearable
                                    onChange={setOpenedAt}
                                />
                                <InputError message={errors.opened_at} />
                            </div>

                            <InputError message={errors.logo} />

                            <div className="flex flex-wrap gap-3">
                                <Button type="submit" disabled={submitting}>
                                    {isEditing
                                        ? t('accounts.save')
                                        : t('accounts.create')}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/accounts">{t('accounts.cancel')}</Link>
                                </Button>
                            </div>

                            {isEditing && account ? (
                                <div className="border-destructive/30 mt-8 space-y-3 border-t pt-6">
                                    <div>
                                        <h2 className="text-destructive text-sm font-semibold">
                                            {t('accounts.delete')}
                                        </h2>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            {t('accounts.delete_confirm', {
                                                name: account.name,
                                            })}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        onClick={() => setDeleteOpen(true)}
                                    >
                                        <Trash2 className="mr-2 size-4" />
                                        {t('accounts.delete')}
                                    </Button>
                                </div>
                            ) : null}
                        </form>
                    </GlassPanel>
                </FadeIn>

                {isEditing && account ? (
                    <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('accounts.delete')}</DialogTitle>
                                <DialogDescription>
                                    {t('accounts.delete_confirm', { name: account.name })}
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setDeleteOpen(false)}
                                >
                                    {t('accounts.cancel')}
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    onClick={() =>
                                        router.delete(`/accounts/${account.id}`)
                                    }
                                >
                                    {t('accounts.delete')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                ) : null}
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