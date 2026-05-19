import { Head, Link, router } from '@inertiajs/react';
import { Archive, Trash2 } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';

import { CreditCardSetupHelp } from '@/components/accounts/credit-card-setup-help';
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
import type {
    AccountType,
    AccountsFormPageProps,
    InvestKind,
    SettlementPeriodMode,
} from '@/types/account.types';

export default function AccountsForm({
    account,
    accountTypes,
    investKindOptions,
    settlementAccountOptions,
    settlementPeriodModeOptions,
}: AccountsFormPageProps) {
    const { t } = useTranslation();
    const isEditing = account !== null;
    const formRef = useRef<HTMLFormElement>(null);
    const [type, setType] = useState<AccountType>(account?.type ?? 'checking');
    const [investKind, setInvestKind] = useState<InvestKind>(
        account?.invest_kind ?? 'securities',
    );
    const [settlementAccountId, setSettlementAccountId] = useState<string>(
        account?.settlement_account_id != null
            ? String(account.settlement_account_id)
            : '',
    );
    const [billingDay, setBillingDay] = useState<string>(
        account?.billing_day != null ? String(account.billing_day) : '',
    );
    const [settlementLabelPattern, setSettlementLabelPattern] = useState<string>(
        account?.settlement_label_pattern ?? (account === null ? 'DEBIT DIFFERE' : ''),
    );
    const [settlementPeriodMode, setSettlementPeriodMode] = useState<SettlementPeriodMode>(
        account?.settlement_period_mode ?? 'since_last_settlement',
    );
    const [accountName, setAccountName] = useState(account?.name ?? '');
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [openedAt, setOpenedAt] = useState<string | null>(account?.opened_at ?? null);
    const [actualBalance, setActualBalance] = useState(
        account !== null ? String(account.current_balance) : '',
    );
    const [submitting, setSubmitting] = useState(false);
    const [archiveOpen, setArchiveOpen] = useState(false);
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

        if (type === 'invest') {
            payload.append('invest_kind', investKind);
        }

        if (type === 'credit_card') {
            if (settlementAccountId !== '') {
                payload.append('settlement_account_id', settlementAccountId);
            }
            if (billingDay !== '') {
                payload.append('billing_day', billingDay);
            }
            const trimmedPattern = settlementLabelPattern.trim();
            if (trimmedPattern !== '') {
                payload.append('settlement_label_pattern', trimmedPattern);
            }
            payload.append('settlement_period_mode', settlementPeriodMode);
        }

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

                            {type === 'invest' ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="invest_kind">{t('accounts.invest_kind')}</Label>
                                    <Select
                                        value={investKind}
                                        onValueChange={(value) =>
                                            setInvestKind(value as InvestKind)
                                        }
                                    >
                                        <SelectTrigger id="invest_kind" className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {investKindOptions.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-muted-foreground text-xs">
                                        {t('accounts.invest_kind_hint')}
                                    </p>
                                    <InputError message={errors.invest_kind} />
                                </div>
                            ) : null}

                            {type === 'credit_card' ? (
                                <>
                                    <CreditCardSetupHelp />
                                    <div className="grid gap-2">
                                        <Label htmlFor="settlement_account_id">
                                            {t('accounts.settlement_account')}
                                        </Label>
                                        <Select
                                            value={
                                                settlementAccountId === ''
                                                    ? undefined
                                                    : settlementAccountId
                                            }
                                            onValueChange={setSettlementAccountId}
                                        >
                                            <SelectTrigger
                                                id="settlement_account_id"
                                                className="w-full"
                                            >
                                                <SelectValue
                                                    placeholder={t(
                                                        'accounts.settlement_account_placeholder',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {settlementAccountOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={String(option.value)}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.settlement_account_id} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="billing_day">
                                            {t('accounts.billing_day')}
                                        </Label>
                                        <Input
                                            id="billing_day"
                                            name="billing_day"
                                            type="number"
                                            min={1}
                                            max={28}
                                            value={billingDay}
                                            onChange={(event) =>
                                                setBillingDay(event.target.value)
                                            }
                                        />
                                        <p className="text-muted-foreground text-xs">
                                            {t('accounts.billing_day_hint')}
                                        </p>
                                        <InputError message={errors.billing_day} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="settlement_label_pattern">
                                            {t('accounts.settlement_label_pattern')}
                                        </Label>
                                        <Input
                                            id="settlement_label_pattern"
                                            name="settlement_label_pattern"
                                            type="text"
                                            maxLength={128}
                                            value={settlementLabelPattern}
                                            placeholder={t(
                                                'accounts.settlement_label_pattern_placeholder',
                                            )}
                                            onChange={(event) =>
                                                setSettlementLabelPattern(event.target.value)
                                            }
                                        />
                                        <p className="text-muted-foreground text-xs">
                                            {t('accounts.settlement_label_pattern_hint')}
                                        </p>
                                        <InputError message={errors.settlement_label_pattern} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="settlement_period_mode">
                                            {t('accounts.settlement_period_mode')}
                                        </Label>
                                        <Select
                                            value={settlementPeriodMode}
                                            onValueChange={(value) =>
                                                setSettlementPeriodMode(
                                                    value as SettlementPeriodMode,
                                                )
                                            }
                                        >
                                            <SelectTrigger id="settlement_period_mode">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {settlementPeriodModeOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-muted-foreground text-xs">
                                            {t('accounts.settlement_period_mode_hint')}
                                        </p>
                                        <InputError message={errors.settlement_period_mode} />
                                    </div>
                                </>
                            ) : null}

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

                            {isEditing && account && !account.is_archived ? (
                                <div className="border-border/60 mt-8 space-y-3 border-t pt-6">
                                    <div>
                                        <h2 className="text-sm font-semibold">
                                            {t('accounts.archive')}
                                        </h2>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            {t('accounts.archive_hint')}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setArchiveOpen(true)}
                                    >
                                        <Archive className="mr-2 size-4" />
                                        {t('accounts.archive')}
                                    </Button>
                                </div>
                            ) : null}

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

                {isEditing && account && !account.is_archived ? (
                    <Dialog open={archiveOpen} onOpenChange={setArchiveOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('accounts.archive')}</DialogTitle>
                                <DialogDescription>
                                    {t('accounts.archive_confirm', { name: account.name })}
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setArchiveOpen(false)}
                                >
                                    {t('accounts.cancel')}
                                </Button>
                                <Button
                                    type="button"
                                    onClick={() => router.post(`/accounts/${account.id}/archive`)}
                                >
                                    {t('accounts.archive')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                ) : null}

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