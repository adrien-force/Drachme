<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\SettlementLabelMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettlementLabelMatcherTest extends TestCase
{
    #[DataProvider('patternProvider')]
    public function test_matches_configured_pattern(?string $pattern, string $label, bool $expected): void
    {
        $this->assertSame($expected, SettlementLabelMatcher::matches($pattern, $label));
    }

    /**
     * @return array<string, array{0: ?string, 1: string, 2: bool}>
     */
    public static function patternProvider(): array
    {
        return [
            'debit differe' => ['DEBIT DIFFERE', 'DEBIT DIFFERE N° 1234107', true],
            'multiple patterns' => ['PRLV CB|DEBIT DIFFERE', 'DEBIT DIFFERE', true],
            'no match' => ['NETFLIX', 'DEBIT DIFFERE', false],
            'empty pattern' => [null, 'DEBIT DIFFERE', false],
        ];
    }

    public function test_matches_generic_debit_differe_keyword(): void
    {
        $this->assertTrue(
            SettlementLabelMatcher::matchesGenericKeywords('DEBIT DIFFERE N° 4107'),
        );
    }
}
