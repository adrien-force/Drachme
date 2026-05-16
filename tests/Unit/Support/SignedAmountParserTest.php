<?php

declare(strict_types=1);


namespace Tests\Unit\Support;

use App\Support\SignedAmountParser;
use PHPUnit\Framework\TestCase;

class SignedAmountParserTest extends TestCase
{
    public function test_parses_plus_and_minus_prefixes(): void
    {
        $parser = new SignedAmountParser;

        $this->assertSame(50.0, $parser->parse('+50'));
        $this->assertSame(-50.0, $parser->parse('-50'));
        $this->assertSame(50.0, $parser->parse('+50,00'));
        $this->assertSame(-120.5, $parser->parse('-120,50'));
    }

    public function test_parses_accounting_parentheses_as_negative(): void
    {
        $parser = new SignedAmountParser;

        $this->assertSame(-85.0, $parser->parse('(85,00)'));
    }
}
