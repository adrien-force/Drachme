<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\DateFormatDetector;
use InvalidArgumentException;
use Tests\TestCase;

class DateFormatDetectorTest extends TestCase
{
    public function test_parse_reports_missing_time_when_format_expects_datetime(): void
    {
        $detector = new DateFormatDetector;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('2022-07-22');
        $this->expectExceptionMessage('Y-m-d H:i:s');

        $detector->parse('2022-07-22', 'Y-m-d H:i:s');
    }

    public function test_parse_accepts_datetime_with_matching_format(): void
    {
        $detector = new DateFormatDetector;

        $parsed = $detector->parse('2022-07-22 01:34:51', 'Y-m-d H:i:s');

        $this->assertSame('2022-07-22 01:34:51', $parsed->format('Y-m-d H:i:s'));
    }
}
