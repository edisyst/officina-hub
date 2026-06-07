<?php

namespace App\Policies;

use App\Models\CompagniaAssicurativa;
use App\Models\User;

class CompagniaAssicurativaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function view(User $user, CompagniaAssicurativa $compagnia): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, CompagniaAssicurativa $compagnia): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, CompagniaAssicurativa $compagnia): bool
    {
        return $user->hasRole('admin');
    }
}
