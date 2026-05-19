<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\CommodityIsin;
use App\Support\Isin;
use Tests\TestCase;

class CommodityIsinTest extends TestCase
{
    public function test_from_label_returns_twelve_character_identifier(): void
    {
        $isin = CommodityIsin::fromLabel('Or');

        $this->assertSame(12, strlen($isin));
        $this->assertMatchesRegularExpression('/^C[A-Z0-9]{11}$/', $isin);
    }

    public function test_from_label_is_deterministic_and_case_insensitive(): void
    {
        $this->assertSame(
            CommodityIsin::fromLabel('Or'),
            CommodityIsin::fromLabel('  or '),
        );
    }

    public function test_from_label_differs_between_labels(): void
    {
        $this->assertNotSame(
            CommodityIsin::fromLabel('Or'),
            CommodityIsin::fromLabel('Argent'),
        );
    }

    public function test_from_label_does_not_collide_with_real_isin_format(): void
    {
        $commodity = CommodityIsin::fromLabel('Or');

        $this->assertStringStartsWith('C', $commodity);
        $this->assertSame(Isin::LENGTH, strlen($commodity));
    }
}
