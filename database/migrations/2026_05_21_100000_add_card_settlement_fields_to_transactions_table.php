<?php

declare(strict_types=1);

use App\Enums\AccountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->boolean('is_card_settlement')->default(false)->after('transfer_pair_id');
            $table->date('card_period_start')->nullable()->after('is_card_settlement');
        });

        $cardAccountIds = DB::table('accounts')
            ->where('type', AccountType::CreditCard->value)
            ->pluck('id');

        if ($cardAccountIds->isNotEmpty()) {
            DB::table('transactions')
                ->whereIn('account_id', $cardAccountIds)
                ->where('amount', '>', 0)
                ->update(['is_card_settlement' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn(['is_card_settlement', 'card_period_start']);
        });
    }
};
