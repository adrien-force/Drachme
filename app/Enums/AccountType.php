<?php

namespace App\Enums;

enum AccountType: string
{
    case Checking = 'checking';
    case Savings = 'savings';
    case Invest = 'invest';
    case Credit = 'credit';
    case Cash = 'cash';
}
