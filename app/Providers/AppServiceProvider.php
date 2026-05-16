<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\Transaction;
use App\Policies\AccountPolicy;
use App\Policies\ImportBatchPolicy;
use App\Policies\ImportProviderPolicy;
use App\Policies\TransactionPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(ImportProvider::class, ImportProviderPolicy::class);
        Gate::policy(ImportBatch::class, ImportBatchPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
