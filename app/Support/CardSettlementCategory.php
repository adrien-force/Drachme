<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;

final class CardSettlementCategory
{
    public function __construct(
        private readonly CategoryService $categories,
    ) {}

    public function ensureForUser(User $user): Category
    {
        $existing = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', Category::SLUG_CARD_SETTLEMENT)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $this->categories->seedDefaultsForUser($user);

        $existing = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', Category::SLUG_CARD_SETTLEMENT)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->createUnderTransfer($user);
    }

    private function createUnderTransfer(User $user): Category
    {
        $transfer = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'transfer')
            ->first();

        $maxSort = Category::query()
            ->where('user_id', $user->id)
            ->where('parent_id', $transfer?->id)
            ->max('sort_order');

        return Category::query()->create([
            'user_id' => $user->id,
            'parent_id' => $transfer?->id,
            'name' => 'Règlement carte',
            'slug' => Category::SLUG_CARD_SETTLEMENT,
            'color' => '#59BDC5',
            'icon' => 'credit-card',
            'is_system' => true,
            'sort_order' => is_numeric($maxSort) ? ((int) $maxSort) + 1 : 0,
        ]);
    }
}
