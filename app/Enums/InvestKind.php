<?php

declare(strict_types=1);

namespace App\Enums;

enum InvestKind: string
{
    case Securities = 'securities';
    case Commodities = 'commodities';
}
