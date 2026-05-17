<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
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
     * }  $data
     */
    public function create(User $user, array $data, ?UploadedFile $logo = null): Account
    {
        $initialBalance = (float) $data['initial_balance'];

        $account = Account::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'institution' => $data['institution'] ?? null,
            'type' => $data['type'] instanceof AccountType
                ? $data['type']
                : AccountType::from((string) $data['type']),
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
     * }  $data
     */
    public function update(
        Account $account,
        array $data,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): Account {
        $account->fill([
            'name' => $data['name'],
            'institution' => $data['institution'] ?? null,
            'type' => $data['type'] instanceof AccountType
                ? $data['type']
                : AccountType::from((string) $data['type']),
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
}
