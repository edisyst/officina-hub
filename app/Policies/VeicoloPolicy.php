<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Veicolo;

class VeicoloPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, Veicolo $veicolo): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, Veicolo $veicolo): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Veicolo $veicolo): bool
    {
        return $user->hasRole('admin');
    }
}
