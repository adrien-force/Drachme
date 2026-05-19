<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Enums\AccountType;
use App\Support\AccountNetWorth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountNetWorthTest extends TestCase
{
    #[DataProvider('liabilityProvider')]
    public function test_liability_amount(AccountType $type, float $balance, float $expected): void
    {
        $this->assertSame($expected, AccountNetWorth::liabilityAmount($type, $balance));
    }

    /**
     * @return array<string, array{AccountType, float, float}>
     */
    public static function liabilityProvider(): array
    {
        return [
            'loan positive balance' => [AccountType::Loan, 10_000.0, 10_000.0],
            'consumer credit positive balance' => [AccountType::Credit, 5_000.0, 5_000.0],
            'credit card negative balance' => [AccountType::CreditCard, -350.0, 350.0],
            'checking overdraft' => [AccountType::Checking, -100.0, 100.0],
        ];
    }

    public function test_credit_card_excluded_from_net_worth(): void
    {
        $this->assertFalse(AccountNetWorth::countsTowardNetWorth(AccountType::CreditCard));
        $this->assertTrue(AccountNetWorth::countsTowardNetWorth(AccountType::Checking));
    }

    public function test_credit_card_amount_owed_from_negative_balance(): void
    {
        $this->assertSame(412.37, AccountNetWorth::creditCardAmountOwed(-412.37));
        $this->assertSame(0.0, AccountNetWorth::creditCardAmountOwed(50.0));
    }
}
