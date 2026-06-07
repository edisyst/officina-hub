<?php

namespace App\Policies;

use App\Models\Scadenza;
use App\Models\User;

class ScadenzaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function view(User $user, Scadenza $scadenza): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, Scadenza $scadenza): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, Scadenza $scadenza): bool
    {
        return $user->hasRole('admin');
    }
}
