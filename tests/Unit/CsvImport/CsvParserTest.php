<?php

declare(strict_types=1);

namespace Tests\Unit\CsvImport;

use App\Services\CsvImport\CsvParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvParserTest extends TestCase
{
    #[Test]
    public function it_strips_utf8_bom_and_parses_semicolon_rows(): void
    {
        $parser = new CsvParser;
        $content = "\xEF\xBB\xBFDate;Label;Amount\n01/01/2024;Coffee;-3,50\n";

        $table = $parser->parse($content, [
            'delimiter' => ';',
            'encoding' => 'UTF-8',
            'skip_rows' => 1,
        ]);

        $this->assertCount(1, $table->rows);
        $this->assertSame(2, $table->rows[0]->lineNumber);
        $this->assertSame(['01/01/2024', 'Coffee', '-3,50'], $table->rows[0]->cells);
    }

    #[Test]
    public function it_converts_iso_8859_1_content(): void
    {
        $parser = new CsvParser;
        $latin = iconv('UTF-8', 'ISO-8859-1', "Café;10\n");
        $table = $parser->parse($latin, [
            'delimiter' => ';',
            'encoding' => 'ISO-8859-1',
        ]);

        $this->assertSame('Café', $table->rows[0]->cells[0]);
    }

    #[Test]
    public function it_rejects_files_that_exceed_row_limit(): void
    {
        $parser = new CsvParser;
        $lines = implode("\n", array_fill(0, CsvParser::MAX_ROWS + 2, 'a;b'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('csv_too_many_rows');

        $parser->parse($lines, ['delimiter' => ';']);
    }
}
