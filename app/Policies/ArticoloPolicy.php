<?php

namespace App\Policies;

use App\Models\Articolo;
use App\Models\User;

class ArticoloPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function view(User $user, Articolo $articolo): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, Articolo $articolo): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, Articolo $articolo): bool
    {
        return $user->hasRole('admin');
    }

    public function movimenta(User $user, Articolo $articolo): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }
}
