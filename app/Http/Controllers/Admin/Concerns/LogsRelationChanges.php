<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\RelationChanges;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * For CRUD controllers whose forms save many-to-many relations: logs what changed in them (RelationChanges).
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
trait LogsRelationChanges
{
    /**
     * Run a store or update operation, logging any change to $relations it made.
     * A request that fails validation throws before anything is saved, so nothing is logged for it.
     *
     * @param  list<string>  $relations
     */
    protected function logRelationChanges(array $relations, Closure $save): mixed
    {
        $id = $this->crud->getCurrentEntryId();
        $entry = $id ? $this->crud->model->newQueryWithoutScopes()->find($id) : null;
        $before = RelationChanges::snapshot($entry, $relations);

        $response = $save();

        if ($this->crud->entry instanceof Model) {
            RelationChanges::log($this->crud->entry, $before);
        }

        return $response;
    }
}
