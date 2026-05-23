<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class SeedDemoDataCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'drachme:seed-demo
                            {--fresh : Rebuild demo user data (demo account only)}';

    /**
     * @var string
     */
    protected $description = 'Seed a dedicated demo user with sample data (does not touch your existing accounts)';

    public function handle(DemoDataSeeder $seeder): int
    {
        $seeder->setCommand($this);
        $seeder->run(fresh: (bool) $this->option('fresh'));

        $this->newLine();
        $this->line('Log in at http://localhost:8080/login');
        $this->line('  Email:    '.DemoDataSeeder::DEMO_EMAIL);
        $this->line('  Password: '.DemoDataSeeder::DEMO_PASSWORD);

        return self::SUCCESS;
    }
}
