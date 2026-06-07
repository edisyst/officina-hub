<?php

namespace App\Policies;

use App\Models\Ponte;
use App\Models\User;

class PontePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Ponte $ponte): bool
    {
        return $user->hasRole('admin');
    }
}
