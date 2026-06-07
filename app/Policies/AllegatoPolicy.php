<?php

namespace App\Policies;

use App\Models\Allegato;
use App\Models\User;

class AllegatoPolicy
{
    public function view(User $user, Allegato $allegato): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']) || $allegato->user_id === $user->id;
    }

    public function delete(User $user, Allegato $allegato): bool
    {
        return $user->hasRole('admin') || $allegato->user_id === $user->id;
    }
}
