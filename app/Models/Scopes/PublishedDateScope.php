<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublishedDateScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // store now so it's consistent
        
        $builder->whereNowOrPast('published_at')
            ->where(function (Builder $query) {
                $query->whereNowOrFuture('unpublished_at')
                    ->orWhereNull('unpublished_at');
            });
    }
}
