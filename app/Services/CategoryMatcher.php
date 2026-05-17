<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\User;

class CategoryMatcher
{
    public function match(User $user, string $label): ?Category
    {
        $haystack = mb_strtolower(trim($label));
        if ($haystack === '') {
            return null;
        }

        $rules = CategoryRule::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->with('category')
            ->get();

        foreach ($rules as $rule) {
            $needle = mb_strtolower(trim($rule->pattern));
            if ($needle === '') {
                continue;
            }

            if (str_contains($haystack, $needle)) {
                return $rule->category;
            }
        }

        return null;
    }
}
