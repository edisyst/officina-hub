<?php

namespace App\Policies;

use App\Models\Fornitore;
use App\Models\User;

class FornitorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function view(User $user, Fornitore $fornitore): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, Fornitore $fornitore): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, Fornitore $fornitore): bool
    {
        return $user->hasRole('admin');
    }
}
