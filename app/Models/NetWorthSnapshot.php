<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class NetWorthSnapshot extends Model
{
    use BelongsToUser;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'total_assets',
        'total_liabilities',
        'net_worth',
        'breakdown',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_assets' => 'decimal:2',
            'total_liabilities' => 'decimal:2',
            'net_worth' => 'decimal:2',
            'breakdown' => 'array',
        ];
    }
}
