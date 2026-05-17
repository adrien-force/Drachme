<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('isin', 12);
            $table->string('label');
            $table->decimal('quantity', 18, 6);
            $table->decimal('average_price', 18, 6);
            $table->decimal('last_price', 18, 6)->nullable();
            $table->timestamp('last_price_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'isin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
