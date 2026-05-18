<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryRuleFlow;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\User;
use App\Support\LabelTokenizer;

class CategoryMatcher
{
    public function match(User $user, string $label, float|string|null $amount = null): ?Category
    {
        $haystack = mb_strtolower(trim($label));
        if ($haystack === '') {
            return null;
        }

        $rules = CategoryRule::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('category')
            ->get();

        $bestRule = null;

        foreach ($rules as $rule) {
            $needle = mb_strtolower(trim($rule->pattern));
            if ($needle === '') {
                continue;
            }

            if (! str_contains($haystack, $needle)) {
                continue;
            }

            if (! $this->ruleMatchesFlow($rule->flow, $amount)) {
                continue;
            }

            if ($bestRule === null || $this->isMoreSpecificThan($rule, $bestRule)) {
                $bestRule = $rule;
            }
        }

        return $bestRule?->category;
    }

    private function ruleMatchesFlow(?CategoryRuleFlow $flow, float|string|null $amount): bool
    {
        if ($flow === null) {
            return true;
        }

        if ($amount === null) {
            return false;
        }

        return $flow->matchesAmount($amount);
    }

    /**
     * Prefer more words in the pattern, then longer pattern, then flow constraint, then priority.
     */
    private function isMoreSpecificThan(CategoryRule $candidate, CategoryRule $incumbent): bool
    {
        $candidateWords = count(LabelTokenizer::tokenize($candidate->pattern));
        $incumbentWords = count(LabelTokenizer::tokenize($incumbent->pattern));

        if ($candidateWords !== $incumbentWords) {
            return $candidateWords > $incumbentWords;
        }

        $candidateLength = mb_strlen(trim($candidate->pattern));
        $incumbentLength = mb_strlen(trim($incumbent->pattern));

        if ($candidateLength !== $incumbentLength) {
            return $candidateLength > $incumbentLength;
        }

        $candidateHasFlow = $candidate->flow !== null;
        $incumbentHasFlow = $incumbent->flow !== null;

        if ($candidateHasFlow !== $incumbentHasFlow) {
            return $candidateHasFlow;
        }

        if ($candidate->priority !== $incumbent->priority) {
            return $candidate->priority > $incumbent->priority;
        }

        return $candidate->id < $incumbent->id;
    }
}
