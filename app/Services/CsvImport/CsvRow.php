<?php

declare(strict_types=1);

namespace App\Services\CsvImport;

readonly class CsvRow
{
    /**
     * @param  list<string>  $cells
     */
    public function __construct(
        public int $lineNumber,
        public array $cells,
    ) {}
}
