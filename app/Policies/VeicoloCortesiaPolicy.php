<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VeicoloCortesia;

class VeicoloCortesiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, VeicoloCortesia $veicolo): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, VeicoloCortesia $veicolo): bool
    {
        return $user->hasRole('admin');
    }
}
