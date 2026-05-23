<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\CategoryDisplayName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_category_slug_is_translated_in_english(): void
    {
        app()->setLocale('en');

        $this->assertSame('Groceries', CategoryDisplayName::for('groceries', 'Courses'));
    }

    public function test_default_category_slug_stays_french_in_french_locale(): void
    {
        app()->setLocale('fr');

        $this->assertSame('Courses', CategoryDisplayName::for('groceries', 'Courses'));
    }

    public function test_custom_category_name_is_preserved(): void
    {
        app()->setLocale('en');

        $this->assertSame('Organic market', CategoryDisplayName::for('groceries', 'Organic market'));
    }

    public function test_category_without_slug_uses_stored_name(): void
    {
        app()->setLocale('en');

        $this->assertSame('My custom bucket', CategoryDisplayName::for(null, 'My custom bucket'));
    }
}
