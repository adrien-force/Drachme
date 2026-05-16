<?php

declare(strict_types=1);

namespace App\Services\CsvImport;

readonly class CsvTable
{
    /**
     * @param  list<CsvRow>  $rows
     */
    public function __construct(
        public array $rows,
    ) {}
}
