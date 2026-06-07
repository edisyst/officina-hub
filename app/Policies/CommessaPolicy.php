<?php

namespace App\Policies;

use App\Enums\StatoCommessa;
use App\Models\Commessa;
use App\Models\User;

class CommessaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Commessa $commessa): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function update(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore']);
    }

    public function delete(User $user, Commessa $commessa): bool
    {
        return $user->hasRole('admin');
    }

    public function accetta(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore'])
            && $commessa->stato === StatoCommessa::Bozza;
    }

    public function avviaLavori(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'meccanico'])
            && $commessa->stato === StatoCommessa::Accettata;
    }

    public function sospendi(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'meccanico'])
            && $commessa->stato === StatoCommessa::InLavorazione;
    }

    public function riprendi(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'meccanico'])
            && $commessa->stato === StatoCommessa::Sospesa;
    }

    public function completa(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'meccanico'])
            && $commessa->stato === StatoCommessa::InLavorazione;
    }

    public function consegna(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'accettatore', 'cassa'])
            && $commessa->stato === StatoCommessa::Completata;
    }

    public function fattura(User $user, Commessa $commessa): bool
    {
        return $user->hasAnyRole(['admin', 'cassa'])
            && $commessa->stato === StatoCommessa::Consegnata;
    }
}
