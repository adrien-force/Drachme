<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'date', 'id'], 'transactions_user_date_id_index');
            $table->index(['user_id', 'account_id', 'date'], 'transactions_user_account_date_index');
            $table->index(['user_id', 'category_id', 'date'], 'transactions_user_category_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_date_id_index');
            $table->dropIndex('transactions_user_account_date_index');
            $table->dropIndex('transactions_user_category_date_index');
        });
    }
};
