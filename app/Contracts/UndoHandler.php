<?php

namespace App\Contracts;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

interface UndoHandler
{
    public function supports(Activity $activity): bool;

    public function undo(Activity $activity, User $user): Activity;
}
