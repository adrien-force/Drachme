<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts') && ! Schema::hasColumn('accounts', 'logo_path')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->string('logo_path')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('import_providers') && ! Schema::hasColumn('import_providers', 'logo_path')) {
            Schema::table('import_providers', function (Blueprint $table) {
                $table->string('logo_path')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('accounts', 'logo_path')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }

        if (Schema::hasColumn('import_providers', 'logo_path')) {
            Schema::table('import_providers', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }
    }
};
