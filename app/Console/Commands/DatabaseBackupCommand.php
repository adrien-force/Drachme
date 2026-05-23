<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup
                            {--path= : Custom output directory (default: storage/backups)}';

    protected $description = 'Create a timestamped SQL backup of the database before sensitive migrations.';

    public function handle(): int
    {
        $connection = (string) config('database.default');
        $directory = $this->option('path') !== null
            ? (string) $this->option('path')
            : storage_path('backups');

        File::ensureDirectoryExists($directory);

        $filename = 'drachme-backup-'.now()->format('Y-m-d_His').'.sql';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        match ($connection) {
            'pgsql' => $this->backupPostgres($path),
            'sqlite' => $this->backupSqlite($path),
            default => throw new RuntimeException("Unsupported database connection [{$connection}] for db:backup."),
        };

        $this->components->info("Database backup written to [{$path}].");

        return self::SUCCESS;
    }

    private function backupPostgres(string $path): void
    {
        $config = config('database.connections.pgsql');
        if (! is_array($config)) {
            throw new RuntimeException('PostgreSQL connection config is missing.');
        }

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '5432');
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new RuntimeException('PostgreSQL database or username is not configured.');
        }

        $result = Process::env([
            'PGPASSWORD' => $password,
        ])->run([
            'pg_dump',
            '--host='.$host,
            '--port='.$port,
            '--username='.$username,
            '--dbname='.$database,
            '--no-owner',
            '--no-acl',
            '--file='.$path,
        ]);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'pg_dump failed.');
        }
    }

    private function backupSqlite(string $path): void
    {
        $config = config('database.connections.sqlite');
        if (! is_array($config)) {
            throw new RuntimeException('SQLite connection config is missing.');
        }

        $database = (string) ($config['database'] ?? '');
        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('Cannot back up an in-memory SQLite database.');
        }

        if (! is_file($database)) {
            throw new RuntimeException("SQLite database file not found at [{$database}].");
        }

        if (! copy($database, $path)) {
            throw new RuntimeException('Failed to copy SQLite database file.');
        }
    }
}
