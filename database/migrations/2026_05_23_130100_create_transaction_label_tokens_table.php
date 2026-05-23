<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_label_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64);
            $table->timestamps();

            $table->index('token_hash');
            $table->unique(['transaction_id', 'token_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_label_tokens');
    }
};
