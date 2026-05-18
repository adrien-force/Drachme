<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\SettlementPeriodMode;
use App\Models\Account;
use App\Models\User;
use App\Support\LogoUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function __construct(
        private readonly LogoUploadService $logos,
        private readonly BalanceEngine $balanceEngine,
        private readonly NetWorthSnapshotService $netWorthSnapshots,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     institution?: string|null,
     *     type: AccountType|string,
     *     initial_balance: float|string,
     *     opened_at?: string|null,
     *     settlement_account_id?: int|string|null,
     *     billing_day?: int|string|null,
     *     settlement_label_pattern?: string|null,
     * }  $data
     */
    public function create(User $user, array $data, ?UploadedFile $logo = null): Account
    {
        $initialBalance = (float) $data['initial_balance'];
        $type = $data['type'] instanceof AccountType
            ? $data['type']
            : AccountType::from((string) $data['type']);

        $account = Account::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'institution' => $data['institution'] ?? null,
            'type' => $type,
            'settlement_account_id' => $this->resolveSettlementAccountId($type, $data),
            'billing_day' => $this->resolveBillingDay($type, $data),
            'settlement_label_pattern' => $this->resolveSettlementLabelPattern($type, $data),
            'settlement_period_mode' => $this->resolveSettlementPeriodMode($type, $data),
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
            'currency' => 'EUR',
            'opened_at' => $data['opened_at'] ?? null,
            'is_archived' => false,
        ]);

        if ($logo !== null) {
            $account->update([
                'logo_path' => $this->logos->store($logo, "logos/accounts/{$user->id}"),
            ]);
        }

        return $account->fresh() ?? $account;
    }

    /**
     * @param  array{
     *     name: string,
     *     institution?: string|null,
     *     type: AccountType|string,
     *     opened_at?: string|null,
     *     actual_balance?: float|string|null,
     *     settlement_account_id?: int|string|null,
     *     billing_day?: int|string|null,
     *     settlement_label_pattern?: string|null,
     * }  $data
     */
    public function update(
        Account $account,
        array $data,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): Account {
        $type = $data['type'] instanceof AccountType
            ? $data['type']
            : AccountType::from((string) $data['type']);

        $account->fill([
            'name' => $data['name'],
            'institution' => $data['institution'] ?? null,
            'type' => $type,
            'settlement_account_id' => $this->resolveSettlementAccountId($type, $data),
            'billing_day' => $this->resolveBillingDay($type, $data),
            'settlement_label_pattern' => $this->resolveSettlementLabelPattern($type, $data),
            'settlement_period_mode' => $this->resolveSettlementPeriodMode($type, $data),
            'opened_at' => $data['opened_at'] ?? null,
            'logo_path' => $this->logos->sync(
                $account->logo_path,
                $logo,
                $removeLogo,
                "logos/accounts/{$account->user_id}",
            ),
        ]);

        $account->save();

        if (array_key_exists('actual_balance', $data) && $data['actual_balance'] !== null && $data['actual_balance'] !== '') {
            $this->balanceEngine->reconcileActualBalance($account, (float) $data['actual_balance']);
            $account->loadMissing('user');
            $owner = $account->user;

            if ($owner !== null) {
                $this->netWorthSnapshots->recordForUser($owner);
            }
        }

        return $account->fresh() ?? $account;
    }

    public function archive(Account $account): void
    {
        $account->update(['is_archived' => true]);
    }

    public function delete(Account $account): void
    {
        $account->loadMissing('user');
        $owner = $account->user;

        DB::transaction(function () use ($account): void {
            $logoPath = $account->logo_path;

            $account->delete();

            $this->logos->delete($logoPath);
        });

        if ($owner !== null) {
            $this->netWorthSnapshots->recordForUser($owner);
        }
    }

    public function logoUrl(Account $account): ?string
    {
        return $this->logos->url($account->logo_path);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSettlementAccountId(AccountType $type, array $data): ?int
    {
        if ($type !== AccountType::CreditCard) {
            return null;
        }

        $raw = $data['settlement_account_id'] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) $raw;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveBillingDay(AccountType $type, array $data): ?int
    {
        if ($type !== AccountType::CreditCard) {
            return null;
        }

        $raw = $data['billing_day'] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) $raw;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSettlementLabelPattern(AccountType $type, array $data): ?string
    {
        if ($type !== AccountType::CreditCard) {
            return null;
        }

        $raw = $data['settlement_label_pattern'] ?? null;

        if ($raw === null) {
            return null;
        }

        $trimmed = trim((string) $raw);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSettlementPeriodMode(AccountType $type, array $data): SettlementPeriodMode
    {
        if ($type !== AccountType::CreditCard) {
            return SettlementPeriodMode::SinceLastSettlement;
        }

        $raw = $data['settlement_period_mode'] ?? SettlementPeriodMode::SinceLastSettlement->value;

        return SettlementPeriodMode::tryFrom((string) $raw)
            ?? SettlementPeriodMode::SinceLastSettlement;
    }
}
