import type { AccountRecord } from '@/types/account.types';

export function accountDisplayBalance(
    account: Pick<AccountRecord, 'type' | 'current_balance' | 'amount_owed' | 'current_period_spend'>,
): number {
    if (account.type === 'credit_card') {
        if (account.current_period_spend !== null) {
            return account.current_period_spend;
        }

        if (account.amount_owed !== null) {
            return account.amount_owed;
        }
    }

    return account.current_balance;
}
