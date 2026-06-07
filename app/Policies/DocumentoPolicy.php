<?php

namespace App\Policies;

use App\Enums\StatoDocumento;
use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'cassa']);
    }

    public function view(User $user, Documento $documento): bool
    {
        return $user->hasAnyRole(['admin', 'cassa']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'cassa']);
    }

    public function update(User $user, Documento $documento): bool
    {
        return $user->hasAnyRole(['admin', 'cassa']) && !$documento->isImmutabile();
    }

    public function delete(User $user, Documento $documento): bool
    {
        return $user->hasRole('admin') && $documento->stato === StatoDocumento::Bozza;
    }

    public function emetti(User $user, Documento $documento): bool
    {
        return $user->hasAnyRole(['admin', 'cassa']) && $documento->stato === StatoDocumento::Bozza;
    }

    public function generaXml(User $user, Documento $documento): bool
    {
        return $user->hasAnyRole(['admin', 'cassa'])
            && in_array($documento->stato, [
                StatoDocumento::Emessa,
                StatoDocumento::InviataSdi,
                StatoDocumento::ScartatasSdi,
            ]);
    }

    public function registraPagamento(User $user, Documento $documento): bool
    {
        return $user->hasAnyRole(['admin', 'cassa'])
            && $documento->stato->accettaPagamenti();
    }

    public function annullaConNotaCredito(User $user, Documento $documento): bool
    {
        return $user->hasAnyRole(['admin', 'cassa'])
            && in_array($documento->stato, [
                StatoDocumento::Emessa,
                StatoDocumento::InviataSdi,
                StatoDocumento::AccettataSdi,
            ]);
    }
}
