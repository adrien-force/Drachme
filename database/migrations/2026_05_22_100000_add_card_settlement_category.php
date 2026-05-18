<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use App\Support\CardSettlementCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $assigner = app(CardSettlementCategory::class);

        User::query()
            ->select('id')
            ->orderBy('id')
            ->each(function (User $user) use ($assigner): void {
                $category = $assigner->ensureForUser($user);

                Transaction::query()
                    ->where('user_id', $user->id)
                    ->where('is_card_settlement', true)
                    ->update(['category_id' => $category->id]);
            });
    }

    public function down(): void
    {
        // System category kept; transactions keep category_id.
    }
};
