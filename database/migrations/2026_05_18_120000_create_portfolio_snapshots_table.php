<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->timestamp('imported_at');
            $table->string('file_signature', 64)->nullable();
            $table->string('original_filename')->nullable();
            $table->decimal('total_market_value', 15, 2);
            $table->unsignedInteger('positions_count');
            $table->json('lines');
            $table->timestamps();

            $table->index(['user_id', 'imported_at']);
            $table->index(['account_id', 'imported_at']);
            $table->index(['user_id', 'file_signature', 'imported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
