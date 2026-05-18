<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use App\Support\Isin;
use App\Support\MarketSymbol;
use InvalidArgumentException;

class PositionService
{
    /**
     * @param  array{
     *     isin: string,
     *     label: string,
     *     quantity: float|string,
     *     average_price: float|string,
     *     last_price?: float|string|null,
     *     market_symbol?: string|null,
     * }  $data
     */
    public function create(User $user, Account $account, array $data): Position
    {
        $this->assertInvestAccount($account);

        return Position::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'isin' => Isin::normalize($data['isin']),
            'market_symbol' => $this->normalizeMarketSymbol($data),
            'label' => $data['label'],
            'quantity' => $data['quantity'],
            'average_price' => $data['average_price'],
            'last_price' => $data['last_price'] ?? null,
            'last_price_at' => array_key_exists('last_price', $data) && $data['last_price'] !== null
                ? now()
                : null,
        ]);
    }

    /**
     * @param  array{
     *     isin: string,
     *     label: string,
     *     quantity: float|string,
     *     average_price: float|string,
     *     last_price?: float|string|null,
     *     market_symbol?: string|null,
     * }  $data
     */
    public function update(Position $position, array $data): Position
    {
        $this->assertInvestAccount($position->account);

        $lastPrice = $data['last_price'] ?? null;
        $lastPriceAt = $position->last_price_at;

        if (array_key_exists('last_price', $data)) {
            $lastPriceAt = $lastPrice !== null ? now() : null;
        }

        $position->fill([
            'isin' => Isin::normalize($data['isin']),
            'market_symbol' => $this->normalizeMarketSymbol($data),
            'label' => $data['label'],
            'quantity' => $data['quantity'],
            'average_price' => $data['average_price'],
            'last_price' => $lastPrice,
            'last_price_at' => $lastPriceAt,
        ]);
        $position->save();

        return $position;
    }

    public function delete(Position $position): void
    {
        $position->delete();
    }

    /**
     * Create or update a position keyed by account + ISIN (CSV import).
     *
     * @param  array{
     *     isin: string,
     *     label: string,
     *     quantity: float|string,
     *     average_price: float|string,
     *     last_price?: float|string|null,
     *     market_symbol?: string|null,
     * }  $data
     */
    public function upsertFromImport(User $user, Account $account, array $data): Position
    {
        $this->assertInvestAccount($account);

        $isin = Isin::normalize($data['isin']);
        $position = Position::query()
            ->where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->where('isin', $isin)
            ->first();

        if ($position === null) {
            return $this->create($user, $account, $data);
        }

        return $this->update($position, $data);
    }

    public function unitPrice(Position $position): float
    {
        $price = $position->last_price ?? $position->average_price;

        return (float) $price;
    }

    public function marketValue(Position $position): float
    {
        return (float) $position->quantity * $this->unitPrice($position);
    }

    public function usesAveragePrice(Position $position): bool
    {
        return $position->last_price === null;
    }

    private function assertInvestAccount(?Account $account): void
    {
        if ($account === null || $account->type !== AccountType::Invest) {
            throw new InvalidArgumentException('positions.not_invest_account');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeMarketSymbol(array $data): ?string
    {
        if (! array_key_exists('market_symbol', $data)) {
            return null;
        }

        return MarketSymbol::normalize(
            is_string($data['market_symbol']) ? $data['market_symbol'] : null,
        );
    }
}
