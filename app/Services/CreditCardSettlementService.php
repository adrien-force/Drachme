<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\CreditCardPeriodResolver;
use App\Support\SettlementLabelMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CreditCardSettlementService
{
    public function __construct(
        private readonly CreditCardPeriodResolver $periodResolver,
    ) {}

    /**
     * @return array{
     *     settlements: list<array{
     *         id: int,
     *         account_id: int,
     *         date: string,
     *         amount: float,
     *         label: string,
     *         is_linked: bool,
     *         spend_matches_settlement: bool,
     *         checking_label: string|null,
     *         checking_date: string|null,
     *         period_start: string,
     *         period_end: string,
     *         period_start_is_manual: bool,
     *         spend_total: float,
     *         purchase_count: int,
     *         purchases: list<array{
     *             id: int,
     *             date: string,
     *             label: string,
     *             amount: float,
     *             category_name: string|null,
     *             category_color: string|null,
     *         }>,
     *     }>,
     *     unmarked_candidates: list<array{
     *         id: int,
     *         account_id: int,
     *         date: string,
     *         amount: float,
     *         label: string,
     *     }>,
     *     open_period: array{
     *         period_start: string,
     *         period_end: string,
     *         spend_total: float,
     *         purchase_count: int,
     *         purchases: list<array{
     *             id: int,
     *             date: string,
     *             label: string,
     *             amount: float,
     *             category_name: string|null,
     *             category_color: string|null,
     *         }>,
     *     }|null,
     * }
     */
    public function build(Account $account): array
    {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);

        if ($type !== AccountType::CreditCard) {
            return ['settlements' => [], 'open_period' => null, 'unmarked_candidates' => []];
        }

        $account->loadMissing('settlementAccount');

        /** @var Collection<int, Transaction> $purchases */
        $purchases = Transaction::query()
            ->where('account_id', $account->id)
            ->where('amount', '<', 0)
            ->with(['category:id,name,color'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $settlementAnchors = $this->resolveSettlementAnchors($account);
        $serializedSettlements = [];
        $previousSettlementDate = null;

        foreach ($settlementAnchors as $settlement) {
            $settlementDate = $this->formatDate($settlement->date);
            $settlementCarbon = CarbonImmutable::parse($settlementDate);

            $periodStart = $settlement->card_period_start !== null
                ? $this->formatDate($settlement->card_period_start)
                : $this->periodResolver->periodStartForSettlement(
                    $account,
                    $settlementCarbon,
                    $previousSettlementDate,
                );

            $periodPurchases = $this->purchasesInPeriod(
                $purchases,
                $periodStart,
                $settlementDate,
            );

            $spendTotal = $this->sumPurchaseAmounts($periodPurchases);
            $settlementAmount = abs((float) $settlement->amount);
            $pair = $settlement->transferPair;
            $isCheckingAnchor = (int) $settlement->account_id !== (int) $account->id;

            $serializedSettlements[] = [
                'id' => $settlement->id,
                'account_id' => $settlement->account_id,
                'date' => $settlementDate,
                'amount' => $settlementAmount,
                'label' => $settlement->label,
                'is_linked' => $settlement->transfer_pair_id !== null,
                'spend_matches_settlement' => abs($spendTotal - $settlementAmount) < 0.01,
                'checking_label' => $isCheckingAnchor ? $settlement->label : $pair?->label,
                'checking_date' => $isCheckingAnchor
                    ? $settlementDate
                    : ($pair !== null ? $this->formatDate($pair->date) : null),
                'period_start' => $periodStart,
                'period_end' => $settlementDate,
                'period_start_is_manual' => $settlement->card_period_start !== null,
                'spend_total' => $spendTotal,
                'purchase_count' => $periodPurchases->count(),
                'purchases' => $this->serializePurchases($periodPurchases),
            ];

            $previousSettlementDate = $settlementCarbon;
        }

        $openPeriodStart = $this->periodResolver->currentOpenPeriodStart(
            $account,
            $previousSettlementDate,
        );

        $openPurchases = $this->purchasesInPeriod(
            $purchases,
            $openPeriodStart,
            CarbonImmutable::today()->format('Y-m-d'),
        );

        $openPeriod = [
            'period_start' => $openPeriodStart,
            'period_end' => CarbonImmutable::today()->format('Y-m-d'),
            'spend_total' => $this->sumPurchaseAmounts($openPurchases),
            'purchase_count' => $openPurchases->count(),
            'purchases' => $this->serializePurchases($openPurchases),
        ];

        return [
            'settlements' => array_reverse($serializedSettlements),
            'open_period' => $openPeriod,
            'unmarked_candidates' => $this->resolveUnmarkedCandidates($account),
        ];
    }

    public function currentPeriodSpend(Account $card): float
    {
        $data = $this->build($card);

        return $data['open_period']['spend_total'] ?? 0.0;
    }

    /**
     * Checking debits (primary) plus legacy card credits marked as settlement.
     *
     * @return Collection<int, Transaction>
     */
    private function resolveSettlementAnchors(Account $card): Collection
    {
        /** @var Collection<int, Transaction> $anchors */
        $anchors = collect();

        $checking = $card->settlementAccount;

        if ($checking !== null) {
            $pattern = trim((string) ($card->settlement_label_pattern ?? ''));

            $checkingSettlements = Transaction::query()
                ->where('account_id', $checking->id)
                ->where('amount', '<', 0)
                ->with(['transferPair:id,date,label,amount,account_id'])
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->filter(
                    fn (Transaction $tx): bool => $tx->is_card_settlement
                        || ($pattern !== '' && SettlementLabelMatcher::matches($pattern, $tx->label)),
                );

            $anchors = $anchors->concat($checkingSettlements);
        }

        $linkedCardIds = $anchors
            ->map(fn (Transaction $tx): ?int => $tx->transfer_pair_id)
            ->filter()
            ->all();

        $legacyCardSettlements = Transaction::query()
            ->where('account_id', $card->id)
            ->where('is_card_settlement', true)
            ->where('amount', '>', 0)
            ->with(['transferPair:id,date,label,amount,account_id'])
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->reject(fn (Transaction $tx): bool => in_array($tx->id, $linkedCardIds, true));

        $anchors = $anchors->concat($legacyCardSettlements);

        return $anchors
            ->sortBy([
                fn (Transaction $tx): string => $this->formatDate($tx->date),
                fn (Transaction $tx): int => $tx->id,
            ])
            ->values();
    }

    /**
     * @return list<array{id: int, account_id: int, date: string, amount: float, label: string}>
     */
    private function resolveUnmarkedCandidates(Account $card): array
    {
        /** @var list<array{id: int, account_id: int, date: string, amount: float, label: string}> $candidates */
        $candidates = array_values(Transaction::query()
            ->where('account_id', $card->id)
            ->where('amount', '>', 0)
            ->where('is_card_settlement', false)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Transaction $tx): array => [
                'id' => $tx->id,
                'account_id' => (int) $tx->account_id,
                'date' => $this->formatDate($tx->date),
                'amount' => (float) $tx->amount,
                'label' => $tx->label,
            ])
            ->all());

        return $candidates;
    }

    /**
     * @param  Collection<int, Transaction>  $purchases
     * @return Collection<int, Transaction>
     */
    private function purchasesInPeriod(
        Collection $purchases,
        string $periodStart,
        string $periodEnd,
    ): Collection {
        return $purchases->filter(
            static function (Transaction $tx) use ($periodStart, $periodEnd): bool {
                $date = $tx->date instanceof \DateTimeInterface
                    ? $tx->date->format('Y-m-d')
                    : (string) $tx->date;

                return $date >= $periodStart && $date <= $periodEnd;
            },
        )->values();
    }

    /**
     * @param  Collection<int, Transaction>  $purchases
     */
    private function sumPurchaseAmounts(Collection $purchases): float
    {
        $sum = 0.0;

        foreach ($purchases as $purchase) {
            $sum += abs((float) $purchase->amount);
        }

        return round($sum, 2);
    }

    /**
     * @param  Collection<int, Transaction>  $purchases
     * @return list<array{
     *     id: int,
     *     date: string,
     *     label: string,
     *     amount: float,
     *     category_name: string|null,
     *     category_color: string|null,
     * }>
     */
    private function serializePurchases(Collection $purchases): array
    {
        /** @var list<array{
         *     id: int,
         *     date: string,
         *     label: string,
         *     amount: float,
         *     category_name: string|null,
         *     category_color: string|null,
         * }> $serialized
         */
        $serialized = array_values($purchases
            ->sortByDesc(static fn (Transaction $tx): string => $tx->date instanceof \DateTimeInterface
                ? $tx->date->format('Y-m-d')
                : (string) $tx->date)
            ->map(function (Transaction $tx): array {
                $category = $tx->category;

                return [
                    'id' => $tx->id,
                    'date' => $this->formatDate($tx->date),
                    'label' => $tx->label,
                    'amount' => (float) $tx->amount,
                    'category_name' => $category?->name,
                    'category_color' => $category?->color,
                ];
            })
            ->all());

        return $serialized;
    }

    private function formatDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return CarbonImmutable::parse((string) $date)->format('Y-m-d');
    }
}
