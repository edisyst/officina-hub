<?php

namespace App\Policies;

use App\Models\PrestitoCortesia;
use App\Models\User;

class PrestitoCortesiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, PrestitoCortesia $prestito): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, PrestitoCortesia $prestito): bool
    {
        return $user->hasRole('admin');
    }
}
