<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;

/**
 * Loads the default category tree (Bankin-style) with localized seed names.
 */
final class DefaultCategoryTree
{
    private const SLUG_UNCATEGORIZED = Category::SLUG_UNCATEGORIZED;

    /**
     * @return list<array{
     *     slug: string,
     *     name: string,
     *     color: string,
     *     icon: string,
     *     is_system?: bool,
     *     children?: list<array<string, mixed>>
     * }>
     */
    public static function definitions(): array
    {
        /** @var list<array<string, mixed>> $raw */
        $raw = json_decode(
            (string) file_get_contents(database_path('data/default_categories.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $definitions = [];
        foreach ($raw as $node) {
            $definitions[] = self::convertNode($node);
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array{
     *     slug: string,
     *     name: string,
     *     color: string,
     *     icon: string,
     *     is_system?: bool,
     *     children?: list<array<string, mixed>>
     * }
     */
    private static function convertNode(array $node): array
    {
        $sourceSlug = (string) $node['name'];
        $slug = $sourceSlug === 'unknown' ? self::SLUG_UNCATEGORIZED : $sourceSlug;

        $entry = [
            'slug' => $slug,
            'name' => CategoryDisplayName::seedName($slug),
            'color' => (string) $node['color'],
            'icon' => (string) $node['icon'],
        ];

        if ($slug === self::SLUG_UNCATEGORIZED || $slug === Category::SLUG_CARD_SETTLEMENT) {
            $entry['is_system'] = true;
        }

        if (($node['is_system'] ?? false) === true) {
            $entry['is_system'] = true;
        }

        $children = [];
        foreach ($node['subcategories'] ?? [] as $sub) {
            if (! is_array($sub)) {
                continue;
            }

            if (($sub['is_custom'] ?? false) === true) {
                continue;
            }

            $children[] = self::convertNode($sub);
        }

        if ($children !== []) {
            $entry['children'] = $children;
        }

        return $entry;
    }
}
