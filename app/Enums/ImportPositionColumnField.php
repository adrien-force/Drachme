<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportPositionColumnField: string
{
    case PositionLabel = 'position_label';
    case Isin = 'isin';
    case Quantity = 'quantity';
    case AveragePrice = 'average_price';
    case LastPrice = 'last_price';
    case Skip = 'skip';
}
