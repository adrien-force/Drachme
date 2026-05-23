<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Facades\Lang;

final class CategoryDisplayName
{
    public static function forCategory(Category $category): string
    {
        return self::for($category->slug, $category->name);
    }

    public static function for(?string $slug, string $storedName, ?string $locale = null): string
    {
        if ($slug === null || $slug === '') {
            return $storedName;
        }

        $key = "ui.categories.defaults.{$slug}";

        if (! Lang::has($key, $locale)) {
            return $storedName;
        }

        if (self::isCustomizedLabel($slug, $storedName)) {
            return $storedName;
        }

        return (string) Lang::get($key, [], $locale);
    }

    public static function seedName(string $slug): string
    {
        $key = "ui.categories.defaults.{$slug}";

        if (! Lang::has($key, 'fr')) {
            return $slug;
        }

        return (string) Lang::get($key, [], 'fr');
    }

    private static function isCustomizedLabel(string $slug, string $storedName): bool
    {
        $frenchSeed = self::seedLabel($slug, 'fr');
        $englishSeed = self::seedLabel($slug, 'en');

        if ($frenchSeed === null && $englishSeed === null) {
            return true;
        }

        return $storedName !== $frenchSeed && $storedName !== $englishSeed;
    }

    private static function seedLabel(string $slug, string $locale): ?string
    {
        $key = "ui.categories.defaults.{$slug}";

        if (! Lang::has($key, $locale)) {
            return null;
        }

        return (string) Lang::get($key, [], $locale);
    }
}
