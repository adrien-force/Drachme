<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label_pattern', 500);
            $table->string('display_label', 500);
            $table->decimal('expected_amount', 15, 2);
            $table->string('frequency');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('occurrence_count')->default(0);
            $table->date('last_seen_at')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'label_pattern']);
        });

        Schema::create('dismissed_recurring_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label_pattern', 500);
            $table->timestamps();

            $table->unique(['user_id', 'label_pattern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dismissed_recurring_patterns');
        Schema::dropIfExists('recurring_patterns');
    }
};
