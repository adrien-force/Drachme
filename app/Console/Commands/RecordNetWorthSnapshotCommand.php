<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NetWorthSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RecordNetWorthSnapshotCommand extends Command
{
    protected $signature = 'drachme:record-net-worth
                            {--user= : Limit to a single user id}
                            {--date= : Snapshot date (Y-m-d), defaults to today}';

    protected $description = 'Record daily net worth snapshots for users';

    public function handle(NetWorthSnapshotService $snapshots): int
    {
        $dateInput = $this->option('date');
        $date = is_string($dateInput) && $dateInput !== ''
            ? CarbonImmutable::parse($dateInput)->startOfDay()
            : CarbonImmutable::today();

        $userId = $this->option('user');

        if (is_string($userId) && $userId !== '') {
            $user = User::query()->find((int) $userId);
            if ($user === null) {
                $this->error('User not found.');

                return self::FAILURE;
            }

            $snapshots->recordForUser($user, $date);
            $this->info("Recorded snapshot for user {$user->id} on {$date->toDateString()}.");

            return self::SUCCESS;
        }

        $count = $snapshots->recordForAllUsers($date);
        $this->info("Recorded {$count} snapshot(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
