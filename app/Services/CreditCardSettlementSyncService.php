<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\CreditCardSettlementSyncResult;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CardSettlementCategory;
use App\Support\CreditCardPeriodResolver;
use App\Support\SettlementLabelMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CreditCardSettlementSyncService
{
    private const SETTLEMENT_DAYS_WINDOW = 7;

    private static bool $syncInProgress = false;

    public function __construct(
        private readonly TransferService $transfers,
        private readonly CreditCardPeriodResolver $periodResolver,
        private readonly CardSettlementCategory $settlementCategory,
    ) {}

    public function syncForUser(User $user): CreditCardSettlementSyncResult
    {
        $total = new CreditCardSettlementSyncResult;

        $cards = Account::query()
            ->where('user_id', $user->id)
            ->where('type', AccountType::CreditCard)
            ->where('is_archived', false)
            ->get();

        foreach ($cards as $card) {
            $result = $this->syncForCard($card);
            $total = $this->mergeResults($total, $result);
        }

        return $total;
    }

    public function syncForAccount(Account $account): CreditCardSettlementSyncResult
    {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);

        if ($type === AccountType::CreditCard) {
            return $this->syncForCard($account);
        }

        $total = new CreditCardSettlementSyncResult;

        $cards = Account::query()
            ->where('user_id', $account->user_id)
            ->where('type', AccountType::CreditCard)
            ->where('settlement_account_id', $account->id)
            ->where('is_archived', false)
            ->get();

        foreach ($cards as $card) {
            $result = $this->syncForCard($card);
            $total = $this->mergeResults($total, $result);
        }

        return $total;
    }

    public function syncForCard(Account $card): CreditCardSettlementSyncResult
    {
        if (self::$syncInProgress) {
            return new CreditCardSettlementSyncResult;
        }

        $type = $card->type instanceof AccountType
            ? $card->type
            : AccountType::from((string) $card->type);

        if ($type !== AccountType::CreditCard) {
            return new CreditCardSettlementSyncResult;
        }

        $card->loadMissing('settlementAccount');

        if ($card->settlement_account_id === null || $card->settlementAccount === null) {
            return new CreditCardSettlementSyncResult(skippedMissingConfig: 1);
        }

        if ($card->settlement_label_pattern === null || trim($card->settlement_label_pattern) === '') {
            return new CreditCardSettlementSyncResult(skippedMissingConfig: 1);
        }

        $user = $card->user;

        if ($user === null) {
            return new CreditCardSettlementSyncResult(skippedMissingConfig: 1);
        }

        self::$syncInProgress = true;

        try {
            return $this->performSync($user, $card, $card->settlementAccount);
        } finally {
            self::$syncInProgress = false;
        }
    }

    private function performSync(User $user, Account $card, Account $checking): CreditCardSettlementSyncResult
    {
        $linkedPairs = 0;
        $markedSettlements = 0;

        /** @var Collection<int, Transaction> $checkingDebits */
        $checkingDebits = Transaction::query()
            ->where('account_id', $checking->id)
            ->where('amount', '<', 0)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Transaction $tx): bool => SettlementLabelMatcher::matches(
                $card->settlement_label_pattern,
                $tx->label,
            ))
            ->values();

        /** @var Collection<int, Transaction> $cardCredits */
        $cardCredits = Transaction::query()
            ->where('account_id', $card->id)
            ->where('amount', '>', 0)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $usedCardIds = [];

        /** @var list<Transaction> $orderedSettlements */
        $orderedSettlements = [];

        foreach ($checkingDebits as $checkingDebit) {
            $cardCredit = $this->resolveCardCreditForCheckingDebit(
                $checkingDebit,
                $cardCredits,
                $usedCardIds,
                $card->id,
            );

            if ($cardCredit !== null) {
                $usedCardIds[] = $cardCredit->id;

                if ($checkingDebit->transfer_pair_id === null && $cardCredit->transfer_pair_id === null) {
                    try {
                        $this->transfers->linkPair($user, $checkingDebit, $cardCredit);
                        $linkedPairs++;
                        $checkingDebit->refresh();
                        $cardCredit->refresh();
                    } catch (InvalidArgumentException) {
                        // Already linked elsewhere or invalid — continue to mark.
                    }
                }
            }

            $orderedSettlements[] = $checkingDebit;
        }

        $previousSettlementDate = null;
        $settlementCategoryId = $this->settlementCategory->ensureForUser($user)->id;

        foreach ($orderedSettlements as $settlement) {
            $settlementDate = CarbonImmutable::parse(
                $settlement->date instanceof \DateTimeInterface
                    ? $settlement->date->format('Y-m-d')
                    : (string) $settlement->date,
            );

            $periodStart = $this->periodResolver->periodStartForSettlement(
                $card,
                $settlementDate,
                $previousSettlementDate,
            );

            $storedStart = $settlement->card_period_start !== null
                ? $this->formatDate($settlement->card_period_start)
                : null;

            $updates = [
                'is_card_settlement' => true,
                'card_period_start' => $periodStart,
                'category_id' => $settlementCategoryId,
            ];

            if (! $settlement->is_card_settlement || $storedStart !== $periodStart || $settlement->category_id !== $settlementCategoryId) {
                $settlement->update($updates);
                $markedSettlements++;
            }

            $previousSettlementDate = $settlementDate;
        }

        return new CreditCardSettlementSyncResult(
            linkedPairs: $linkedPairs,
            markedSettlements: $markedSettlements,
        );
    }

    /**
     * @param  Collection<int, Transaction>  $cardCredits
     * @param  list<int>  $usedCardIds
     */
    private function resolveCardCreditForCheckingDebit(
        Transaction $checkingDebit,
        Collection $cardCredits,
        array $usedCardIds,
        int $cardAccountId,
    ): ?Transaction {
        if ($checkingDebit->transfer_pair_id !== null) {
            $pair = $checkingDebit->transferPair;

            if ($pair !== null && (int) $pair->account_id === $cardAccountId && (float) $pair->amount > 0) {
                return $pair;
            }
        }

        $targetAmount = abs((float) $checkingDebit->amount);
        $checkingDate = CarbonImmutable::parse(
            $checkingDebit->date instanceof \DateTimeInterface
                ? $checkingDebit->date->format('Y-m-d')
                : (string) $checkingDebit->date,
        );

        $best = null;
        $bestDayDistance = PHP_INT_MAX;

        foreach ($cardCredits as $candidate) {
            if (in_array($candidate->id, $usedCardIds, true)) {
                continue;
            }

            if ($candidate->transfer_pair_id !== null && $candidate->transfer_pair_id !== $checkingDebit->id) {
                continue;
            }

            if (abs(abs((float) $candidate->amount) - $targetAmount) >= 0.001) {
                continue;
            }

            $candidateDate = CarbonImmutable::parse(
                $candidate->date instanceof \DateTimeInterface
                    ? $candidate->date->format('Y-m-d')
                    : (string) $candidate->date,
            );

            $dayDistance = (int) $checkingDate->diffInDays($candidateDate, absolute: true);

            if ($dayDistance > self::SETTLEMENT_DAYS_WINDOW) {
                continue;
            }

            if ($dayDistance < $bestDayDistance) {
                $best = $candidate;
                $bestDayDistance = $dayDistance;
            }
        }

        return $best;
    }

    private function formatDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return CarbonImmutable::parse((string) $date)->format('Y-m-d');
    }

    private function mergeResults(
        CreditCardSettlementSyncResult $left,
        CreditCardSettlementSyncResult $right,
    ): CreditCardSettlementSyncResult {
        return new CreditCardSettlementSyncResult(
            linkedPairs: $left->linkedPairs + $right->linkedPairs,
            markedSettlements: $left->markedSettlements + $right->markedSettlements,
            skippedMissingConfig: $left->skippedMissingConfig + $right->skippedMissingConfig,
            skippedNoMatch: $left->skippedNoMatch + $right->skippedNoMatch,
        );
    }
}
