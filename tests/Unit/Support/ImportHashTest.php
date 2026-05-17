<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ImportHash;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportHashTest extends TestCase
{
    #[Test]
    public function make_and_disambiguate_produce_64_char_hashes(): void
    {
        $base = ImportHash::make(4, Carbon::parse('2024-06-04'), -7.99, 'PayPal');
        $variant = ImportHash::disambiguate($base, 13, 463);

        $this->assertSame(64, strlen($base));
        $this->assertSame(64, strlen($variant));
        $this->assertNotSame($base, $variant);
    }
}
