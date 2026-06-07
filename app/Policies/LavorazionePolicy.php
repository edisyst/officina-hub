<?php

namespace App\Policies;

use App\Models\Lavorazione;
use App\Models\User;

class LavorazionePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'meccanico']);
    }

    public function avvia(User $user, Lavorazione $lavorazione): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $lavorazione->user_id === $user->id;
    }

    public function ferma(User $user, Lavorazione $lavorazione): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $lavorazione->user_id === $user->id;
    }
}
