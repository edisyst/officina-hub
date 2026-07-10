<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

trait EmitsActionCompleted
{
    protected function markLastActivityUndoable(Model $model): ?int
    {
        $activity = Activity::where('subject_type', get_class($model))
            ->where('subject_id', $model->id)
            ->latest('id')
            ->first();

        if ($activity && ! isset($activity->properties['undoable'])) {
            $activity->properties = $activity->properties->merge(['undoable' => true]);
            $activity->save();
        }

        return $activity?->id;
    }

    protected function emitActionCompleted(string $message, ?int $activityId = null): void
    {
        $this->dispatch('action-completed', message: $message, activityId: $activityId);
    }
}
