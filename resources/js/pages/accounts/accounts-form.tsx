import { Head, Link, router } from '@inertiajs/react';
import { Archive, Trash2 } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';

import { CreditCardSetupHelp } from '@/components/accounts/credit-card-setup-help';
import { LoanAccountHelp } from '@/components/accounts/loan-account-help';
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
    SettlementPeriodMode,
} from '@/types/account.types';

export default function AccountsForm({
    account,
    accountTypes,
    settlementAccountOptions,
    settlementPeriodModeOptions,
}: AccountsFormPageProps) {
    const { t } = useTranslation();
    const isEditing = account !== null;
    const formRef = useRef<HTMLFormElement>(null);
    const [type, setType] = useState<AccountType>(account?.type ?? 'checking');
    const [settlementAccountId, setSettlementAccountId] = useState<string>(
        account?.settlement_account_id != null
            ? String(account.settlement_account_id)
            : '',
    );
    const [billingDay, setBillingDay] = useState<string>(
        account?.billing_day != null ? String(account.billing_day) : '',
    );
    const [paymentDay, setPaymentDay] = useState<string>(
        account?.payment_day != null ? String(account.payment_day) : '',
    );
    const [loanOriginalPrincipal, setLoanOriginalPrincipal] = useState<string>(
        account?.loan_original_principal != null
            ? String(account.loan_original_principal)
            : '',
    );
    const [loanInterestRate, setLoanInterestRate] = useState<string>(
        account?.loan_interest_rate != null ? String(account.loan_interest_rate) : '',
    );
    const [loanEndDate, setLoanEndDate] = useState<string | null>(
        account?.loan_end_date ?? null,
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

    const loanEndDatePickerBounds = useMemo(() => {
        const today = new Date();

        return {
            startMonth: today,
            endMonth: new Date(today.getFullYear() + 40, 11, 31),
        };
    }, []);

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

        if (type === 'loan') {
            if (paymentDay !== '') {
                payload.append('payment_day', paymentDay);
            }
            if (loanOriginalPrincipal !== '') {
                payload.append('loan_original_principal', loanOriginalPrincipal);
            }
            if (loanInterestRate !== '') {
                payload.append('loan_interest_rate', loanInterestRate);
            }
            if (loanEndDate !== null && loanEndDate !== '') {
                payload.append('loan_end_date', loanEndDate);
            }
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

                            {type === 'loan' ? (
                                <>
                                    <LoanAccountHelp />
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="loan_original_principal">
                                                {t('accounts.loan_original_principal')}
                                            </Label>
                                            <Input
                                                id="loan_original_principal"
                                                type="number"
                                                min={0}
                                                step="0.01"
                                                value={loanOriginalPrincipal}
                                                onChange={(event) =>
                                                    setLoanOriginalPrincipal(event.target.value)
                                                }
                                                placeholder={t(
                                                    'accounts.loan_original_principal_placeholder',
                                                )}
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                {t('accounts.loan_original_principal_hint')}
                                            </p>
                                            <InputError message={errors.loan_original_principal} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="loan_interest_rate">
                                                {t('accounts.loan_interest_rate')}
                                            </Label>
                                            <Input
                                                id="loan_interest_rate"
                                                type="number"
                                                min={0}
                                                max={100}
                                                step="0.01"
                                                value={loanInterestRate}
                                                onChange={(event) =>
                                                    setLoanInterestRate(event.target.value)
                                                }
                                                placeholder={t(
                                                    'accounts.loan_interest_rate_placeholder',
                                                )}
                                            />
                                            <InputError message={errors.loan_interest_rate} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="payment_day">
                                                {t('accounts.payment_day')}
                                            </Label>
                                            <Input
                                                id="payment_day"
                                                type="number"
                                                min={1}
                                                max={31}
                                                value={paymentDay}
                                                onChange={(event) =>
                                                    setPaymentDay(event.target.value)
                                                }
                                                placeholder={t(
                                                    'accounts.payment_day_placeholder',
                                                )}
                                            />
                                            <InputError message={errors.payment_day} />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label>{t('accounts.loan_start_date')}</Label>
                                            <DatePicker
                                                value={openedAt}
                                                onChange={setOpenedAt}
                                                endMonth={loanEndDatePickerBounds.endMonth}
                                            />
                                            <InputError message={errors.opened_at} />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label>{t('accounts.loan_end_date')}</Label>
                                            <DatePicker
                                                value={loanEndDate}
                                                onChange={setLoanEndDate}
                                                startMonth={loanEndDatePickerBounds.startMonth}
                                                endMonth={loanEndDatePickerBounds.endMonth}
                                                clearable
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                {t('accounts.loan_end_date_hint')}
                                            </p>
                                            <InputError message={errors.loan_end_date} />
                                        </div>
                                    </div>
                                </>
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

                            {!isEditing && type !== 'loan' ? (
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

                            {isEditing && account && balanceReconcile && account.type !== 'loan' ? (
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
                                            {account.type === 'loan'
                                                ? t('accounts.loan_outstanding_balance')
                                                : t('accounts.actual_balance')}
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

                            {type !== 'loan' ? (
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
                            ) : null}

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