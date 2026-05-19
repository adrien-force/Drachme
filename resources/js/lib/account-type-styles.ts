import type { AccountType } from '@/types/account.types';

export const accountTypeBadgeClass: Record<AccountType, string> = {
    checking: 'border-sky-500/30 bg-sky-500/15 text-sky-700 dark:text-sky-300',
    savings: 'border-emerald-500/30 bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
    invest: 'border-violet-500/30 bg-violet-500/15 text-violet-700 dark:text-violet-300',
    credit: 'border-rose-500/30 bg-rose-500/15 text-rose-700 dark:text-rose-300',
    loan: 'border-orange-500/30 bg-orange-500/15 text-orange-800 dark:text-orange-300',
    credit_card: 'border-fuchsia-500/30 bg-fuchsia-500/15 text-fuchsia-700 dark:text-fuchsia-300',
    cash: 'border-amber-500/30 bg-amber-500/15 text-amber-800 dark:text-amber-300',
};
