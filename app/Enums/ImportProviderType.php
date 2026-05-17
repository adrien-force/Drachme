<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportProviderType: string
{
    case Transactions = 'transactions';
    case Positions = 'positions';
}
