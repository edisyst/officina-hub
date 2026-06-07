<?php

namespace App\Policies;

use App\Models\DviIspezione;
use App\Models\User;

class DviIspezionePolicy
{
    public function update(User $user, DviIspezione $ispezione): bool
    {
        return $user->hasAnyRole(['admin', 'meccanico', 'accettatore']);
    }

    public function view(User $user, DviIspezione $ispezione): bool
    {
        return $user->hasAnyRole(['admin', 'meccanico', 'accettatore', 'cassa']);
    }
}
