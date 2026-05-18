<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\RecurringSuggestion;
use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\DismissedRecurringPattern;
use App\Models\RecurringPattern;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RecurringLabelNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class RecurringDetector
{
    private const MIN_OCCURRENCES = 3;

    private const AMOUNT_TOLERANCE = 0.05;

    private const FREQUENCY_MATCH_RATIO = 0.7;

    public function __construct(
        private readonly RecurringLabelNormalizer $labelNormalizer,
        private readonly CategoryMatcher $categoryMatcher,
    ) {}

    /**
     * @return list<RecurringSuggestion>
     */
    public function findSuggestions(User $user, int $lookbackMonths = 24): array
    {
        $since = CarbonImmutable::today()->subMonths($lookbackMonths);

        /** @var Collection<int, Transaction> $transactions */
        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('transfer_pair_id')
            ->whereIn('type', [TransactionType::Expense, TransactionType::Income])
            ->whereDate('date', '>=', $since->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'user_id', 'account_id', 'date', 'label', 'amount', 'type', 'category_id']);

        if ($transactions->count() < self::MIN_OCCURRENCES) {
            return [];
        }

        $dismissedKeys = DismissedRecurringPattern::query()
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(
                fn (DismissedRecurringPattern $row): array => [
                    $this->patternGroupKey($row->label_pattern, $row->transaction_type) => true,
                ],
            );

        $confirmedKeys = RecurringPattern::query()
            ->where('user_id', $user->id)
            ->where('is_confirmed', true)
            ->get()
            ->mapWithKeys(
                fn (RecurringPattern $row): array => [
                    $this->patternGroupKey($row->label_pattern, $row->transaction_type) => true,
                ],
            );

        /** @var array<string, list<Transaction>> $groups */
        $groups = [];

        foreach ($transactions as $transaction) {
            $pattern = $this->labelNormalizer->normalize($transaction->label);

            if ($this->labelNormalizer->isGeneric($pattern)) {
                continue;
            }

            $type = $transaction->type instanceof TransactionType
                ? $transaction->type->value
                : (string) $transaction->type;
            $groups[$pattern.'|'.$type][] = $transaction;
        }

        $suggestions = [];

        foreach ($groups as $groupKey => $group) {
            if ($dismissedKeys->has($groupKey) || $confirmedKeys->has($groupKey)) {
                continue;
            }

            if (count($group) < self::MIN_OCCURRENCES) {
                continue;
            }

            [$labelPattern] = explode('|', $groupKey, 2);
            $suggestion = $this->buildSuggestion($user, $labelPattern, $group);

            if ($suggestion !== null && $suggestion->score >= 60) {
                $suggestions[] = $suggestion;
            }
        }

        usort(
            $suggestions,
            static fn (RecurringSuggestion $a, RecurringSuggestion $b): int => $b->score <=> $a->score
                ?: $b->occurrenceCount <=> $a->occurrenceCount,
        );

        return $suggestions;
    }

    /**
     * @param  list<Transaction>  $group
     */
    private function buildSuggestion(User $user, string $labelPattern, array $group): ?RecurringSuggestion
    {
        usort(
            $group,
            static fn (Transaction $a, Transaction $b): int => CarbonImmutable::parse($a->date)->getTimestamp()
                <=> CarbonImmutable::parse($b->date)->getTimestamp(),
        );

        $amounts = array_map(
            static fn (Transaction $transaction): float => abs((float) $transaction->amount),
            $group,
        );

        if (! $this->amountsWithinTolerance($amounts)) {
            return null;
        }

        $dates = array_map(
            static fn (Transaction $transaction): CarbonImmutable => CarbonImmutable::parse($transaction->date),
            $group,
        );

        $frequency = $this->detectFrequency($dates);

        if ($frequency === null) {
            return null;
        }

        $medianAmount = $this->median($amounts);
        $expectedAmount = number_format($medianAmount, 2, '.', '');

        $displayLabel = $this->resolveDisplayLabel($group);
        $lastTransaction = $group[count($group) - 1];
        $suggestedCategoryId = $this->resolveSuggestedCategoryId($user, $group);
        $transactionType = $lastTransaction->type instanceof TransactionType
            ? $lastTransaction->type
            : TransactionType::from((string) $lastTransaction->type);

        return new RecurringSuggestion(
            labelPattern: $labelPattern,
            displayLabel: $displayLabel,
            expectedAmount: $expectedAmount,
            frequency: $frequency,
            transactionType: $transactionType,
            occurrenceCount: count($group),
            score: $this->scoreSuggestion($labelPattern, $dates, $frequency),
            suggestedCategoryId: $suggestedCategoryId,
            accountId: $lastTransaction->account_id,
            sampleTransactions: array_slice($group, -3),
        );
    }

    /**
     * @param  list<float>  $amounts
     */
    private function amountsWithinTolerance(array $amounts): bool
    {
        $median = $this->median($amounts);

        if ($median < 0.01) {
            return false;
        }

        foreach ($amounts as $amount) {
            if (abs($amount - $median) / $median > self::AMOUNT_TOLERANCE) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<CarbonImmutable>  $dates
     */
    private function detectFrequency(array $dates): ?RecurringFrequency
    {
        if (count($dates) < self::MIN_OCCURRENCES) {
            return null;
        }

        $gaps = [];

        for ($index = 1; $index < count($dates); $index++) {
            $gaps[] = (int) $dates[$index - 1]->diffInDays($dates[$index], absolute: true);
        }

        $gapCount = count($gaps);

        if ($gapCount === 0) {
            return null;
        }

        $bestMatch = null;
        $bestRatio = 0.0;
        $bestDeviation = PHP_FLOAT_MAX;

        foreach (RecurringFrequency::cases() as $frequency) {
            $matches = 0;

            foreach ($gaps as $gap) {
                if ($frequency->gapMatches($gap)) {
                    $matches++;
                }
            }

            $ratio = $matches / $gapCount;

            if ($ratio < self::FREQUENCY_MATCH_RATIO) {
                continue;
            }

            $deviation = 0.0;

            foreach ($gaps as $gap) {
                $deviation += abs($gap - $frequency->targetDays());
            }

            $averageDeviation = $deviation / $gapCount;

            if (
                $ratio > $bestRatio
                || ($ratio === $bestRatio && $averageDeviation < $bestDeviation)
            ) {
                $bestMatch = $frequency;
                $bestRatio = $ratio;
                $bestDeviation = $averageDeviation;
            }
        }

        return $bestMatch;
    }

    /**
     * @param  list<CarbonImmutable>  $dates
     */
    private function scoreSuggestion(
        string $labelPattern,
        array $dates,
        RecurringFrequency $frequency,
    ): int {
        $score = 50;

        if (mb_strlen($labelPattern) >= 8) {
            $score += 15;
        }

        if (count($dates) >= 4) {
            $score += 10;
        }

        if (count($dates) >= 6) {
            $score += 5;
        }

        $gaps = [];

        for ($index = 1; $index < count($dates); $index++) {
            $gaps[] = (int) $dates[$index - 1]->diffInDays($dates[$index], absolute: true);
        }

        $target = $frequency->targetDays();
        $deviation = 0.0;

        foreach ($gaps as $gap) {
            $deviation += abs($gap - $target);
        }

        $averageDeviation = count($gaps) > 0 ? $deviation / count($gaps) : $target;
        $score += (int) max(0, 20 - $averageDeviation);

        return min(100, $score);
    }

    private function patternGroupKey(string $labelPattern, TransactionType|string $type): string
    {
        $typeValue = $type instanceof TransactionType ? $type->value : $type;

        return $labelPattern.'|'.$typeValue;
    }

    /**
     * @param  list<Transaction>  $group
     */
    private function resolveDisplayLabel(array $group): string
    {
        $labels = array_map(static fn (Transaction $transaction): string => $transaction->label, $group);
        $counts = array_count_values($labels);
        arsort($counts);

        $mostCommon = array_key_first($counts);

        return is_string($mostCommon) ? $mostCommon : $group[0]->label;
    }

    /**
     * @param  list<Transaction>  $group
     */
    private function resolveSuggestedCategoryId(User $user, array $group): ?int
    {
        $categoryCounts = [];

        foreach ($group as $transaction) {
            if ($transaction->category_id !== null) {
                $categoryCounts[$transaction->category_id] = ($categoryCounts[$transaction->category_id] ?? 0) + 1;
            }
        }

        if ($categoryCounts !== []) {
            arsort($categoryCounts);

            return (int) array_key_first($categoryCounts);
        }

        $matched = $this->categoryMatcher->match($user, $group[0]->label, $group[0]->amount);

        return $matched?->id;
    }

    /**
     * @param  list<float>  $values
     */
    private function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
