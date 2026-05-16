<?php

namespace App\Enums;

enum ImportColumnField: string
{
    case Date = 'date';
    case Label = 'label';
    case AmountSigned = 'amount_signed';
    case Debit = 'debit';
    case Credit = 'credit';
    case Balance = 'balance';
    case Skip = 'skip';
}
