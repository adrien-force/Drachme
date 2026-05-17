<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Utf8Normalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Utf8NormalizerTest extends TestCase
{
    #[Test]
    public function it_sanitizes_nested_preview_payload(): void
    {
        $invalid = "bad\x80label";

        $sanitized = Utf8Normalizer::sanitizeArray([
            'label' => $invalid,
            'rows' => [
                ['isin' => $invalid],
            ],
        ]);

        $this->assertTrue(mb_check_encoding((string) $sanitized['label'], 'UTF-8'));
        $this->assertTrue(mb_check_encoding((string) $sanitized['rows'][0]['isin'], 'UTF-8'));

        $encoded = json_encode($sanitized, JSON_THROW_ON_ERROR);
        $this->assertIsString($encoded);
    }
}
