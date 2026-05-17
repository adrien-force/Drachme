<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Isin;
use PHPUnit\Framework\TestCase;

class IsinTest extends TestCase
{
    public function test_normalize_uppercases_and_trims(): void
    {
        $this->assertSame('FR0012633286', Isin::normalize(' fr0012633286 '));
    }

    public function test_valid_isin_accepts_twelve_alphanumeric_characters(): void
    {
        $this->assertTrue(Isin::isValid('FR0012633286'));
        $this->assertFalse(Isin::isValid('FR001263328'));
        $this->assertFalse(Isin::isValid('FR00126332861'));
        $this->assertFalse(Isin::isValid('FR001263328!'));
    }
}
