<?php

declare(strict_types=1);

namespace App\Enums;

enum SettlementPeriodMode: string
{
    case SinceLastSettlement = 'since_last_settlement';
    case CalendarMonth = 'calendar_month';
    case BillingCycle = 'billing_cycle';
}
