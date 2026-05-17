<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_patterns', function (Blueprint $table) {
            $table->string('transaction_type', 32)->default('expense')->after('frequency');
            $table->dropUnique(['user_id', 'label_pattern']);
            $table->unique(['user_id', 'label_pattern', 'transaction_type'], 'recurring_patterns_user_label_type_unique');
        });

        Schema::table('dismissed_recurring_patterns', function (Blueprint $table) {
            $table->string('transaction_type', 32)->default('expense')->after('label_pattern');
            $table->dropUnique(['user_id', 'label_pattern']);
            $table->unique(
                ['user_id', 'label_pattern', 'transaction_type'],
                'dismissed_recurring_user_label_type_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('dismissed_recurring_patterns', function (Blueprint $table) {
            $table->dropUnique('dismissed_recurring_user_label_type_unique');
            $table->dropColumn('transaction_type');
            $table->unique(['user_id', 'label_pattern']);
        });

        Schema::table('recurring_patterns', function (Blueprint $table) {
            $table->dropUnique('recurring_patterns_user_label_type_unique');
            $table->dropColumn('transaction_type');
            $table->unique(['user_id', 'label_pattern']);
        });
    }
};
