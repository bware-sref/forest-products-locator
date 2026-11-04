<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Logs changes to many-to-many relations (a user's roles, a mill's wood species), which are saved with sync() and so
 * never reach the activity log on their own: sync() fires no model events.
 *
 * Take a snapshot before saving and hand it to log() afterwards:
 *
 *     $before = RelationChanges::snapshot($mill, ['millTypes', 'woodSpecies']);
 *     // ...save...
 *     RelationChanges::log($mill, $before);
 *
 * The entry uses the same old/attributes shape as Spatie's own, so the activity log screen shows it as a diff,
 * with each relation as a comma-separated list of names.
 */
final class RelationChanges
{
    /**
     * @param  list<string>  $relations
     * @return array<string, string>
     */
    public static function snapshot(?Model $model, array $relations): array
    {
        return collect($relations)
            ->mapWithKeys(fn (string $relation) => [
                $relation => $model?->exists ? self::describe($model->{$relation}()->get()) : '',
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $before  from snapshot()
     */
    public static function log(Model $model, array $before): void
    {
        $after = self::snapshot($model, array_keys($before));
        $changed = array_keys(array_diff_assoc($after, $before));

        if (! $changed) {
            return;
        }

        activity()
            ->performedOn($model)
            ->event('updated')
            ->withProperties([
                ...ActivitySource::properties(),
                'old' => array_intersect_key($before, array_flip($changed)),
                'attributes' => array_intersect_key($after, array_flip($changed)),
            ])
            ->useLog(ActivitySource::current())
            ->log('updated '.implode(' and ', array_map(fn ($relation) => Str::lower(Str::headline($relation)), $changed)));
    }

    private static function describe(Collection $related): string
    {
        return $related
            ->map(fn (Model $item) => (string) ($item->getAttribute('name') ?? $item->getKey()))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->implode(', ');
    }
}
