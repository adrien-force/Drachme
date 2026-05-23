<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\TransactionLabelTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final class EncryptFinancialDataCommand extends Command
{
    protected $signature = 'drachme:encrypt-financial-data
                            {--skip-backup : Do not run db:backup first (not recommended)}
                            {--key= : CipherSweet key (defaults to CIPHERSWEET_KEY from .env)}';

    protected $description = 'Back up the database, encrypt transaction labels/notes, and rebuild search indexes.';

    public function handle(TransactionLabelTokenService $labelTokens): int
    {
        $key = $this->option('key') !== null
            ? (string) $this->option('key')
            : (string) config('ciphersweet.providers.string.key');

        if ($key === '') {
            $this->components->error('CIPHERSWEET_KEY is missing. Run: php artisan ciphersweet:generate-key');

            return self::FAILURE;
        }

        if (! $this->option('skip-backup')) {
            $this->components->info('Creating database backup…');
            $backupExit = Artisan::call('db:backup');
            $this->line(Artisan::output());

            if ($backupExit !== self::SUCCESS) {
                return self::FAILURE;
            }
        } else {
            $this->components->warn('Skipping database backup (--skip-backup).');
        }

        $this->components->info('Encrypting transaction labels and notes…');
        $encryptExit = Artisan::call('ciphersweet:encrypt', [
            'model' => Transaction::class,
            'newKey' => $key,
        ]);
        $this->line(Artisan::output());

        if ($encryptExit !== self::SUCCESS) {
            throw new RuntimeException('ciphersweet:encrypt failed.');
        }

        $this->components->info('Rebuilding label search token indexes…');
        $count = $labelTokens->reindexAll();
        $this->components->info("Indexed {$count} transaction(s).");

        $this->components->info('Financial data encryption complete.');
        $this->line('Store CIPHERSWEET_KEY securely (password manager / secrets vault). Without it, encrypted data cannot be recovered.');

        return self::SUCCESS;
    }
}
