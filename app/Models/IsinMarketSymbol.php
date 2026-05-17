<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['isin', 'symbol', 'source'])]
class IsinMarketSymbol extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'isin';
}
