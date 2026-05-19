<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\InvestKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('invest_kind', 32)->nullable()->after('type');
        });

        DB::table('accounts')
            ->where('type', AccountType::Invest->value)
            ->update(['invest_kind' => InvestKind::Securities->value]);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('invest_kind');
        });
    }
};
