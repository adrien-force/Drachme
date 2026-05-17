<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dismissed_transfer_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_a_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('transaction_b_id')->constrained('transactions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'transaction_a_id', 'transaction_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dismissed_transfer_suggestions');
    }
};
