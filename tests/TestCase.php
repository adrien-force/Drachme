<?php

declare(strict_types=1);


namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Laravel 13 CSRF (PreventRequestForgery) only skips verification when env is
        // "testing". Cached config keeps APP_ENV=local — send same-origin for feature tests.
        $this->withHeader('Sec-Fetch-Site', 'same-origin');
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
