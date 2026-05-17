<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isin_market_symbols', function (Blueprint $table) {
            $table->string('isin', 12)->primary();
            $table->string('symbol', 32);
            $table->string('source', 32)->default('search');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isin_market_symbols');
    }
};
