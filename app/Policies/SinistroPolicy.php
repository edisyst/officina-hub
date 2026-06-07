<?php

namespace App\Policies;

use App\Models\Sinistro;
use App\Models\User;

class SinistroPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function view(User $user, Sinistro $sinistro): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, Sinistro $sinistro): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, Sinistro $sinistro): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }
}
