<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\ImportColumnField;
use App\Enums\ImportProviderType;
use App\Enums\InvestKind;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\NetWorthSnapshot;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BalanceEngine;
use App\Services\CategoryService;
use App\Services\ImportProviderService;
use App\Services\NetWorthSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a dedicated demo user with realistic sample data.
 * Never touches other users or their data.
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public const DEMO_EMAIL = 'demo@drachme.test';

    public const DEMO_PASSWORD = 'password';

    private const MARKER_ACCOUNT = 'Main Checking';

    public const DEMO_PROVIDER_NAME = 'Chase CSV';

    public const DEMO_CSV_SAMPLE = 'database/data/demo/chase-checking-export.csv';

    public function __construct(
        private readonly CategoryService $categories,
        private readonly BalanceEngine $balanceEngine,
        private readonly NetWorthSnapshotService $netWorthSnapshots,
        private readonly ImportProviderService $importProviders,
    ) {}

    public function run(bool $fresh = false): void
    {
        $user = User::query()->where('email', self::DEMO_EMAIL)->first();

        if ($user !== null && $fresh) {
            $this->wipeDemoUserData($user);
        }

        if ($user === null) {
            $user = $this->createDemoUser();
        } elseif ($this->isDemoSeeded($user) && ! $fresh) {
            $this->command?->info('Demo data already present for '.self::DEMO_EMAIL.' (use --fresh to rebuild).');

            return;
        }

        $this->categories->seedDefaultsForUser($user);

        $checking = $this->createAccount($user, [
            'name' => self::MARKER_ACCOUNT,
            'institution' => 'Chase',
            'type' => AccountType::Checking,
            'initial_balance' => '450.00',
            'opened_at' => now()->subYears(2)->startOfMonth(),
        ]);

        $savings = $this->createAccount($user, [
            'name' => 'Emergency Savings',
            'institution' => 'Marcus',
            'type' => AccountType::Savings,
            'initial_balance' => '3200.00',
            'opened_at' => now()->subYears(3)->startOfMonth(),
        ]);

        $brokerage = $this->createAccount($user, [
            'name' => 'Brokerage',
            'institution' => 'Trade Republic',
            'type' => AccountType::Invest,
            'invest_kind' => InvestKind::Securities,
            'initial_balance' => '0.00',
            'opened_at' => now()->subYear()->startOfMonth(),
        ]);

        $this->seedTransactions($user, $checking, $savings);
        $this->seedImportProvider($user, $checking);
        $this->seedPosition($user, $brokerage);
        $this->seedPortfolioSnapshots($user, $brokerage);

        foreach ([$checking, $savings, $brokerage] as $account) {
            $this->balanceEngine->recalculateAccount($account->fresh());
        }

        $this->netWorthSnapshots->recordForUser($user);

        $this->command?->info('Demo user ready: '.self::DEMO_EMAIL.' / '.self::DEMO_PASSWORD);
        $this->command?->info('Sample import CSV: '.self::DEMO_CSV_SAMPLE);
    }

    private function createDemoUser(): User
    {
        $user = User::query()->create([
            'name' => 'Alex Morgan',
            'email' => self::DEMO_EMAIL,
            'password' => Hash::make(self::DEMO_PASSWORD),
            'locale' => 'en',
            'month_start_day' => 1,
            'email_verified_at' => now(),
        ]);

        $this->categories->seedDefaultsForUser($user);

        return $user;
    }

    private function isDemoSeeded(User $user): bool
    {
        return Account::query()
            ->where('user_id', $user->id)
            ->where('name', self::MARKER_ACCOUNT)
            ->exists();
    }

    private function wipeDemoUserData(User $user): void
    {
        DB::transaction(function () use ($user): void {
            Transaction::query()->where('user_id', $user->id)->delete();
            ImportBatch::query()->where('user_id', $user->id)->delete();
            ImportProvider::query()->where('user_id', $user->id)->delete();
            Position::query()->where('user_id', $user->id)->delete();
            Account::query()->where('user_id', $user->id)->delete();
            NetWorthSnapshot::query()->where('user_id', $user->id)->delete();
            PortfolioSnapshot::query()->where('user_id', $user->id)->delete();
            Category::query()->where('user_id', $user->id)->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAccount(User $user, array $attributes): Account
    {
        $balance = (string) ($attributes['initial_balance'] ?? '0.00');

        return Account::query()->create([
            'user_id' => $user->id,
            'name' => $attributes['name'],
            'institution' => $attributes['institution'] ?? null,
            'type' => $attributes['type'],
            'invest_kind' => $attributes['invest_kind'] ?? null,
            'initial_balance' => $balance,
            'current_balance' => $balance,
            'currency' => 'EUR',
            'opened_at' => $attributes['opened_at'] ?? null,
            'is_archived' => false,
        ]);
    }

    private function seedTransactions(User $user, Account $checking, Account $savings): void
    {
        $categoryIds = Category::query()
            ->where('user_id', $user->id)
            ->pluck('id', 'slug');

        $today = CarbonImmutable::today();
        $start = $today->subMonths(5)->startOfMonth();
        $currentMonth = $today->startOfMonth();

        /** @var list<array{day: int, label: string, amount: string, type: TransactionType, slug: string|null, account: Account}> $templates */
        $templates = [
            ['day' => 1, 'label' => 'Monthly rent', 'amount' => '-920.00', 'type' => TransactionType::Expense, 'slug' => 'rent', 'account' => $checking],
            ['day' => 3, 'label' => 'City Power & Gas', 'amount' => '-78.40', 'type' => TransactionType::Expense, 'slug' => 'electricity', 'account' => $checking],
            ['day' => 5, 'label' => 'Fiber internet', 'amount' => '-39.99', 'type' => TransactionType::Expense, 'slug' => 'internet', 'account' => $checking],
            ['day' => 6, 'label' => 'Mobile plan', 'amount' => '-24.00', 'type' => TransactionType::Expense, 'slug' => 'mobile_phone', 'account' => $checking],
            ['day' => 8, 'label' => 'Whole Foods Market', 'amount' => '-62.15', 'type' => TransactionType::Expense, 'slug' => 'groceries', 'account' => $checking],
            ['day' => 12, 'label' => 'Shell Gas Station', 'amount' => '-54.30', 'type' => TransactionType::Expense, 'slug' => 'fuel', 'account' => $checking],
            ['day' => 14, 'label' => 'Blue Bottle Coffee', 'amount' => '-5.80', 'type' => TransactionType::Expense, 'slug' => 'coffee_shop', 'account' => $checking],
            ['day' => 16, 'label' => 'Spotify Premium', 'amount' => '-10.99', 'type' => TransactionType::Expense, 'slug' => 'subscriptions', 'account' => $checking],
            ['day' => 18, 'label' => 'Trader Joe\'s', 'amount' => '-48.70', 'type' => TransactionType::Expense, 'slug' => 'groceries', 'account' => $checking],
            ['day' => 21, 'label' => 'The Corner Bistro', 'amount' => '-34.50', 'type' => TransactionType::Expense, 'slug' => 'dining_out', 'account' => $checking],
            ['day' => 25, 'label' => 'ACME Pharmacy', 'amount' => '-18.25', 'type' => TransactionType::Expense, 'slug' => 'personal_care', 'account' => $checking],
            ['day' => 27, 'label' => 'Metro Transit pass', 'amount' => '-42.00', 'type' => TransactionType::Expense, 'slug' => 'public_transport', 'account' => $checking],
            ['day' => 28, 'label' => 'Acme Corp payroll', 'amount' => '2650.00', 'type' => TransactionType::Income, 'slug' => 'salary_income', 'account' => $checking],
            ['day' => 29, 'label' => 'Internal transfer to savings', 'amount' => '-200.00', 'type' => TransactionType::Expense, 'slug' => 'internal_transfer', 'account' => $checking],
            ['day' => 29, 'label' => 'Internal transfer from checking', 'amount' => '200.00', 'type' => TransactionType::Income, 'slug' => 'internal_transfer', 'account' => $savings],
        ];

        for ($month = $start; $month->lessThanOrEqualTo($currentMonth); $month = $month->addMonth()) {
            foreach ($templates as $template) {
                $day = min($template['day'], $month->daysInMonth);
                $date = $month->day($day);

                if ($date->greaterThan($today)) {
                    continue;
                }

                $slug = $template['slug'];
                $categoryId = $slug !== null ? ($categoryIds[$slug] ?? null) : null;

                Transaction::query()->create([
                    'user_id' => $user->id,
                    'account_id' => $template['account']->id,
                    'date' => $date,
                    'label' => $template['label'],
                    'amount' => $template['amount'],
                    'type' => $template['type'],
                    'category_id' => $categoryId,
                ]);
            }
        }

        Transaction::query()->create([
            'user_id' => $user->id,
            'account_id' => $savings->id,
            'date' => $today->subMonths(2)->day(15),
            'label' => 'Annual bonus',
            'amount' => '750.00',
            'type' => TransactionType::Income,
            'category_id' => $categoryIds['bonuses'] ?? null,
        ]);
    }

    private function seedImportProvider(User $user, Account $checking): void
    {
        $this->importProviders->create($user, [
            'name' => self::DEMO_PROVIDER_NAME,
            'default_account_id' => $checking->id,
            'account_ids' => [$checking->id],
            'import_type' => ImportProviderType::Transactions,
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::Debit->value],
                    ['index' => 3, 'field' => ImportColumnField::Credit->value],
                ],
            ],
            'csv_options' => [
                'delimiter' => ';',
                'enclosure' => '"',
                'encoding' => 'UTF-8',
                'skip_rows' => 1,
                'date_format' => 'd/m/Y',
            ],
        ]);
    }

    private function seedPosition(User $user, Account $brokerage): void
    {
        Position::query()->create([
            'user_id' => $user->id,
            'account_id' => $brokerage->id,
            'isin' => 'IE00B4L5Y983',
            'market_symbol' => 'IWDA.AS',
            'label' => 'iShares Core MSCI World',
            'quantity' => '8.000000',
            'average_price' => '82.500000',
            'last_price' => '91.250000',
            'last_price_at' => now(),
        ]);
    }

    private function seedPortfolioSnapshots(User $user, Account $brokerage): void
    {
        $today = CarbonImmutable::today();
        $quantity = 8.0;
        $averagePrice = 82.5;

        /** @var list<array{months_ago: int, price: float}> $imports */
        $imports = [
            ['months_ago' => 4, 'price' => 78.0],
            ['months_ago' => 3, 'price' => 82.0],
            ['months_ago' => 2, 'price' => 85.0],
            ['months_ago' => 1, 'price' => 88.0],
            ['months_ago' => 0, 'price' => 91.25],
        ];

        foreach ($imports as $import) {
            $importedAt = $today->subMonths($import['months_ago'])->day(12)->setTime(10, 30);

            if ($importedAt->greaterThan($today->endOfDay())) {
                $importedAt = $today->setTime(10, 30);
            }

            $marketValue = round($quantity * $import['price'], 2);

            PortfolioSnapshot::query()->create([
                'user_id' => $user->id,
                'account_id' => $brokerage->id,
                'imported_at' => $importedAt,
                'original_filename' => 'brokerage-statement-'.$importedAt->format('Y-m').'.csv',
                'total_market_value' => number_format($marketValue, 2, '.', ''),
                'positions_count' => 1,
                'lines' => [[
                    'isin' => 'IE00B4L5Y983',
                    'label' => 'iShares Core MSCI World',
                    'quantity' => $quantity,
                    'average_price' => $averagePrice,
                    'last_price' => $import['price'],
                    'market_value' => $marketValue,
                ]],
            ]);
        }
    }
}
