<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DefaultCategoryTree;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CategoryService
{
    private const MAX_DEPTH = 3;

    private const LEGACY_SEED_MARKER_SLUG = 'personal_and_home_essentials';

    public function seedDefaultsForUser(User $user): void
    {
        if ($this->hasCurrentDefaultSeed($user)) {
            return;
        }

        if (Category::query()->where('user_id', $user->id)->exists()) {
            $hasLinkedTransactions = Transaction::query()
                ->where('user_id', $user->id)
                ->whereNotNull('category_id')
                ->exists();

            if ($hasLinkedTransactions) {
                return;
            }

            Category::query()->where('user_id', $user->id)->delete();
        }

        $this->seedTreeForUser($user, DefaultCategoryTree::definitions());
    }

    /**
     * @param list<array{
     *     slug: string,
     *     name: string,
     *     color: string,
     *     icon: string,
     *     is_system?: bool,
     *     children?: list<array<string, mixed>>
     * }> $definitions
     */
    private function seedTreeForUser(User $user, array $definitions, ?int $parentId = null): void
    {
        foreach ($definitions as $index => $definition) {
            $category = Category::query()->create([
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'color' => $definition['color'],
                'icon' => $definition['icon'],
                'is_system' => $definition['is_system'] ?? false,
                'sort_order' => $index,
            ]);

            /** @var list<array{
             *     slug: string,
             *     name: string,
             *     color: string,
             *     icon: string,
             *     is_system?: bool,
             *     children?: list<array<string, mixed>>
             * }> $children */
            $children = $definition['children'] ?? [];
            if ($children !== []) {
                $this->seedTreeForUser($user, $children, $category->id);
            }
        }
    }

    private function hasCurrentDefaultSeed(User $user): bool
    {
        return Category::query()
            ->where('user_id', $user->id)
            ->where('slug', self::LEGACY_SEED_MARKER_SLUG)
            ->exists();
    }

    /**
     * @param array{
     *     name: string,
     *     parent_id?: int|null,
     *     color?: string|null,
     *     icon?: string|null,
     * } $data
     */
    public function create(User $user, array $data): Category
    {
        $parent = $this->resolveParent($user, $data['parent_id'] ?? null);
        $this->assertDepthAllowsChild($parent);

        $maxSort = Category::query()
            ->where('user_id', $user->id)
            ->where('parent_id', $parent?->id)
            ->max('sort_order');

        return Category::query()->create([
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => is_numeric($maxSort) ? ((int) $maxSort) + 1 : 0,
        ]);
    }

    /**
     * @param array{
     *     name: string,
     *     parent_id?: int|null,
     *     color?: string|null,
     *     icon?: string|null,
     * } $data
     */
    public function update(Category $category, array $data): Category
    {
        if ($category->isUncategorized()) {
            throw new InvalidArgumentException('category_system_protected');
        }

        $user = User::query()->findOrFail($category->user_id);
        $parent = $this->resolveParent($user, $data['parent_id'] ?? null, $category);

        if ($parent !== null && $this->isDescendantOf($parent, $category)) {
            throw new InvalidArgumentException('category_circular_parent');
        }

        $this->assertDepthAllowsChild($parent, $category);

        $category->update([
            'name' => $data['name'],
            'parent_id' => $parent?->id,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        return $category->refresh();
    }

    public function delete(Category $category, ?Category $mergeTarget): void
    {
        if ($category->isUncategorized()) {
            throw new InvalidArgumentException('category_system_protected');
        }

        if ($category->children()->exists()) {
            throw new InvalidArgumentException('category_has_children');
        }

        $transactionCount = $category->transactions()->count();

        if ($transactionCount > 0) {
            if ($mergeTarget === null) {
                throw new InvalidArgumentException('category_merge_required');
            }

            if ($mergeTarget->user_id !== $category->user_id) {
                throw new InvalidArgumentException('category_merge_forbidden');
            }

            if ($mergeTarget->id === $category->id) {
                throw new InvalidArgumentException('category_merge_same');
            }

            DB::transaction(function () use ($category, $mergeTarget): void {
                Transaction::query()
                    ->where('category_id', $category->id)
                    ->update(['category_id' => $mergeTarget->id]);

                $category->delete();
            });

            return;
        }

        $category->delete();
    }

    public function depth(Category $category): int
    {
        $depth = 1;
        $parentId = $category->parent_id;

        while ($parentId !== null) {
            $depth++;
            $parentId = Category::query()->whereKey($parentId)->value('parent_id');
        }

        return $depth;
    }

    private function resolveParent(User $user, ?int $parentId, ?Category $except = null): ?Category
    {
        if ($parentId === null || $parentId === 0) {
            return null;
        }

        $parent = Category::query()
            ->where('user_id', $user->id)
            ->whereKey($parentId)
            ->first();

        if ($parent === null) {
            throw new InvalidArgumentException('category_parent_forbidden');
        }

        if ($except !== null && $parent->id === $except->id) {
            throw new InvalidArgumentException('category_circular_parent');
        }

        return $parent;
    }

    private function assertDepthAllowsChild(?Category $parent, ?Category $moving = null): void
    {
        $parentDepth = $parent !== null ? $this->depth($parent) : 0;
        $subtreeDepth = $moving !== null ? $this->maxDescendantDepthOffset($moving) : 0;
        $resultingDepth = $parentDepth + 1 + $subtreeDepth;

        if ($resultingDepth > self::MAX_DEPTH) {
            throw new InvalidArgumentException('category_max_depth');
        }
    }

    private function maxDescendantDepthOffset(Category $category): int
    {
        $children = Category::query()->where('parent_id', $category->id)->get();
        if ($children->isEmpty()) {
            return 0;
        }

        $max = 0;
        foreach ($children as $child) {
            $max = max($max, 1 + $this->maxDescendantDepthOffset($child));
        }

        return $max;
    }

    private function isDescendantOf(Category $candidate, Category $ancestor): bool
    {
        $current = $candidate->parent;

        while ($current !== null) {
            if ($current->id === $ancestor->id) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }

    /**
     * Flat list for selects (indented by depth).
     *
     * @return list<array{
     *     id: int,
     *     parent_id: int|null,
     *     name: string,
     *     depth: int,
     *     color: string|null,
     *     is_system: bool,
     *     sort_order: int,
     * }>
     */
    public function flatSelectOptions(User $user): array
    {
        $categories = Category::query()
            ->where('user_id', $user->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'color', 'is_system', 'slug', 'sort_order']);

        /** @var array<int, int> $depthById */
        $depthById = [];

        $resolveDepth = function (Category $category) use (&$resolveDepth, &$depthById, $categories): int {
            if (isset($depthById[$category->id])) {
                return $depthById[$category->id];
            }

            if ($category->parent_id === null) {
                $depthById[$category->id] = 1;

                return 1;
            }

            $parent = $categories->firstWhere('id', $category->parent_id);
            $depth = $parent !== null ? $resolveDepth($parent) + 1 : 1;
            $depthById[$category->id] = $depth;

            return $depth;
        };

        $options = [];
        foreach ($categories as $category) {
            $options[] = [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'depth' => $resolveDepth($category),
                'color' => $category->color,
                'is_system' => $category->is_system,
                'sort_order' => $category->sort_order,
            ];
        }

        usort(
            $options,
            static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']
                ?: strcmp($a['name'], $b['name']),
        );

        return $options;
    }
}
