<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->foreignId('settlement_account_id')
                ->nullable()
                ->after('type')
                ->constrained('accounts')
                ->nullOnDelete();
            $table->unsignedTinyInteger('billing_day')->nullable()->after('settlement_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('settlement_account_id');
            $table->dropColumn('billing_day');
        });
    }
};
