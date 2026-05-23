<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TransactionLabelTokenService;
use Illuminate\Console\Command;

final class ReindexTransactionLabelTokensCommand extends Command
{
    protected $signature = 'drachme:reindex-transaction-label-tokens';

    protected $description = 'Rebuild blind-index token rows used to search encrypted transaction labels.';

    public function handle(TransactionLabelTokenService $labelTokens): int
    {
        $count = $labelTokens->reindexAll();
        $this->components->info("Reindexed {$count} transaction(s).");

        return self::SUCCESS;
    }
}
