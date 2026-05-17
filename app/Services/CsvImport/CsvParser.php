<?php

declare(strict_types=1);

namespace App\Services\CsvImport;

use App\Support\Utf8Normalizer;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

class CsvParser
{
    public const int MAX_BYTES = 10 * 1024 * 1024;

    public const int MAX_ROWS = 50_000;

    /**
     * @param  array{
     *     delimiter?: string,
     *     encoding?: string,
     *     skip_rows?: int,
     * }  $options
     */
    public function parse(UploadedFile|string $source, array $options = []): CsvTable
    {
        $content = $this->readContent($source);
        $delimiter = $this->resolveDelimiter($options['delimiter'] ?? ',');
        $encoding = (string) ($options['encoding'] ?? 'UTF-8');
        $skipRows = max(0, (int) ($options['skip_rows'] ?? 0));

        $content = $this->stripBom($content);
        $content = Utf8Normalizer::ensureValid($this->convertEncoding($content, $encoding));

        $rows = [];
        $lineNumber = 0;
        $dataRowIndex = 0;

        foreach ($this->iterateLines($content) as $line) {
            $lineNumber++;

            if ($line === '') {
                continue;
            }

            if ($dataRowIndex < $skipRows) {
                $dataRowIndex++;

                continue;
            }

            $cells = str_getcsv($line, $delimiter, '"', '\\');
            $rows[] = new CsvRow($lineNumber, array_map(
                static fn (?string $cell): string => Utf8Normalizer::ensureValid(trim((string) $cell)),
                $cells,
            ));

            if (count($rows) > self::MAX_ROWS) {
                throw new InvalidArgumentException('csv_too_many_rows');
            }

            $dataRowIndex++;
        }

        return new CsvTable($rows);
    }

    private function readContent(UploadedFile|string $source): string
    {
        if ($source instanceof UploadedFile) {
            $path = $source->getRealPath();
            if ($path === false) {
                throw new RuntimeException('csv_unreadable');
            }

            $size = $source->getSize();
            if ($size !== false && $size > self::MAX_BYTES) {
                throw new InvalidArgumentException('csv_too_large');
            }

            $content = file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException('csv_unreadable');
            }

            if (strlen($content) > self::MAX_BYTES) {
                throw new InvalidArgumentException('csv_too_large');
            }

            return $content;
        }

        if (strlen($source) > self::MAX_BYTES) {
            throw new InvalidArgumentException('csv_too_large');
        }

        return $source;
    }

    private function stripBom(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }

        return $content;
    }

    private function convertEncoding(string $content, string $encoding): string
    {
        $normalized = strtoupper(str_replace(['_', ' '], '-', $encoding));

        if (in_array($normalized, ['UTF-8', 'UTF8'], true)) {
            return $content;
        }

        $sourceEncoding = match ($normalized) {
            'ISO-8859-1', 'ISO8859-1', 'LATIN1', 'LATIN-1' => 'ISO-8859-1',
            'WINDOWS-1252', 'CP1252' => 'Windows-1252',
            default => null,
        };

        if ($sourceEncoding === null) {
            throw new InvalidArgumentException('csv_encoding_unsupported');
        }

        $converted = iconv($sourceEncoding, 'UTF-8//IGNORE', $content);

        if ($converted === false) {
            throw new InvalidArgumentException('csv_encoding_failed');
        }

        return $converted;
    }

    private function resolveDelimiter(string $delimiter): string
    {
        return match ($delimiter) {
            ',', ';', "\t" => $delimiter,
            'tab' => "\t",
            default => ',',
        };
    }

    /**
     * @return \Generator<int, string, mixed, void>
     */
    private function iterateLines(string $content): \Generator
    {
        $handle = fopen('php://memory', 'rb+');
        if ($handle === false) {
            throw new RuntimeException('csv_unreadable');
        }

        fwrite($handle, $content);
        rewind($handle);

        while (($line = fgets($handle)) !== false) {
            yield rtrim($line, "\r\n");
        }

        fclose($handle);
    }
}
