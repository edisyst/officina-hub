<?php

namespace App\Services;

use App\Models\Documento;

/**
 * Conservazione sostitutiva documenti fiscali — STUB.
 *
 * TODO: la conservazione sostitutiva a norma (D.Lgs. 82/2005, CAD) richiede
 *       un servizio accreditato AgID. Attivare solo dopo aver stipulato un
 *       contratto con un conservatore accreditato (es. Aruba, Namirial, ecc.)
 *       e aver configurato i parametri in config/conservazione.php.
 *
 * Attivazione: impostare `conservazione_sostitutiva_abilitata = 1` in settings
 *              e implementare il metodo `conserva()` con le API del conservatore.
 */
class ConservazioneService
{
    public function isAbilitata(): bool
    {
        return setting('conservazione_sostitutiva_abilitata', '0') === '1';
    }

    public function conserva(Documento $documento): void
    {
        if (! $this->isAbilitata()) {
            throw new \RuntimeException(
                'Conservazione sostitutiva non abilitata. ' .
                'Abilitarla in Impostazioni dopo aver configurato il servizio accreditato AgID.'
            );
        }

        // TODO: implementare integrazione con il conservatore accreditato
        throw new \RuntimeException('Conservazione sostitutiva non ancora implementata.');
    }
}
