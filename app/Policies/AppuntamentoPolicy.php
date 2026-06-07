<?php

namespace App\Policies;

use App\Models\Appuntamento;
use App\Models\User;

class AppuntamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, Appuntamento $appuntamento): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, Appuntamento $appuntamento): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }
}
