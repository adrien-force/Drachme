<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Enums\AccountType;
use App\Enums\SettlementPeriodMode;
use App\Models\Account;
use App\Models\User;
use App\Support\CreditCardPeriodResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardPeriodResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_open_period_start_uses_active_billing_cycle(): void
    {
        CarbonImmutable::setTestNow('2026-05-17');

        $user = User::factory()->create();
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'billing_day' => 4,
            'settlement_period_mode' => SettlementPeriodMode::BillingCycle,
        ]);

        $resolver = app(CreditCardPeriodResolver::class);
        $lastSettlement = CarbonImmutable::parse('2026-04-04');

        $start = $resolver->currentOpenPeriodStart($card, $lastSettlement);

        $this->assertSame('2026-05-05', $start);

        CarbonImmutable::setTestNow();
    }
}
