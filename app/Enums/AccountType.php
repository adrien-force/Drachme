<?php

declare(strict_types=1);


namespace App\Enums;

enum AccountType: string
{
    case Checking = 'checking';
    case Savings = 'savings';
    case Invest = 'invest';
    case Credit = 'credit';
    case Loan = 'loan';
    case CreditCard = 'credit_card';
    case Cash = 'cash';
}
