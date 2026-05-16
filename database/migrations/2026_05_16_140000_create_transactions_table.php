<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('label', 500);
            $table->decimal('amount', 15, 2);
            $table->string('type');
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('import_hash', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->unique(['account_id', 'import_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
