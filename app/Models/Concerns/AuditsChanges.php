<?php

namespace App\Models\Concerns;

use App\Support\ActivitySource;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Records every create, update and delete in the activity log: which fields changed, from what, to what,
 * who did it, and where the change came from (ActivitySource).
 *
 * Only the saved model's own columns are seen. Many-to-many changes (a mill's types, a user's roles) are saved with
 * sync(), which fires no model events, so the controllers that save them log those separately (RelationChanges).
 * So do bulk query updates and deletes, such as an import replacing a state's mills (FinalizeMillImport).
 */
trait AuditsChanges
{
    use LogsActivity {
        shouldLogEvent as activitylogShouldLogEvent;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at', ...$this->attributesNotAudited()])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Columns left out of the log, such as secrets.
     *
     * @return list<string>
     */
    protected function attributesNotAudited(): array
    {
        return [];
    }

    /**
     * Whether this particular change should be left out of the log.
     */
    protected function skipsAudit(string $eventName): bool
    {
        return false;
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        return ! $this->skipsAudit($eventName) && $this->activitylogShouldLogEvent($eventName);
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->log_name = ActivitySource::current();

        if ($extra = ActivitySource::properties()) {
            $activity->properties = $activity->properties->merge($extra);
        }
    }
}
