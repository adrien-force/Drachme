<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->decimal('loan_original_principal', 15, 2)->nullable()->after('payment_day');
            $table->decimal('loan_interest_rate', 8, 4)->nullable()->after('loan_original_principal');
            $table->date('loan_end_date')->nullable()->after('loan_interest_rate');
        });

        DB::table('accounts')
            ->where('type', 'credit')
            ->update(['type' => 'loan']);
    }

    public function down(): void
    {
        DB::table('accounts')
            ->where('type', 'loan')
            ->update(['type' => 'credit']);

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'loan_original_principal',
                'loan_interest_rate',
                'loan_end_date',
            ]);
        });
    }
};
