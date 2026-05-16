<?php

declare(strict_types=1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('institution')->nullable()->after('name');
            $table->string('type')->default('checking')->after('institution');
            $table->decimal('initial_balance', 15, 2)->default(0)->after('type');
            $table->decimal('current_balance', 15, 2)->default(0)->after('initial_balance');
            $table->char('currency', 3)->default('EUR')->after('current_balance');
            $table->date('opened_at')->nullable()->after('currency');
            $table->boolean('is_archived')->default(false)->after('opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'institution',
                'type',
                'initial_balance',
                'current_balance',
                'currency',
                'opened_at',
                'is_archived',
            ]);
        });
    }
};
