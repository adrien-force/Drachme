<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_provider_account')) {
            Schema::create('import_provider_account', function (Blueprint $table) {
                $table->id();
                $table->foreignId('import_provider_id')->constrained()->cascadeOnDelete();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['import_provider_id', 'account_id']);
            });
        }

        if (! Schema::hasTable('import_providers')) {
            return;
        }

        $rows = DB::table('import_providers')
            ->whereNotNull('default_account_id')
            ->get(['id', 'default_account_id']);

        foreach ($rows as $row) {
            DB::table('import_provider_account')->insertOrIgnore([
                'import_provider_id' => $row->id,
                'account_id' => $row->default_account_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_provider_account');
    }
};
