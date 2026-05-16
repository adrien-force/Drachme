<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new BelongsToUserScope);

        static::creating(function (Model $model): void {
            if (auth()->check() && empty($model->getAttribute('user_id'))) {
                $model->setAttribute('user_id', auth()->id());
            }
        });
    }

    /**
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function isOwnedByCurrentUser(): bool
    {
        return auth()->check() && (int) $this->user_id === (int) auth()->id();
    }

    /**
     * Route binding: 404 if missing, 403 if owned by another user.
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $field ??= $this->getRouteKeyName();

        $model = static::withoutGlobalScope(BelongsToUserScope::class)
            ->where($field, $value)
            ->first();

        if ($model === null) {
            abort(404);
        }

        if (! $model->isOwnedByCurrentUser()) {
            abort(403);
        }

        /** @var static $model */
        return $model;
    }
}
