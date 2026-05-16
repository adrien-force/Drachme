<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @implements Scope<Model>
 */
class BelongsToUserScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $builder->where($model->getTable().'.user_id', auth()->id());
    }
}
