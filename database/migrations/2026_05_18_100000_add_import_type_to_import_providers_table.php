<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_providers', function (Blueprint $table) {
            $table->string('import_type', 32)
                ->default('transactions')
                ->after('default_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('import_providers', function (Blueprint $table) {
            $table->dropColumn('import_type');
        });
    }
};
