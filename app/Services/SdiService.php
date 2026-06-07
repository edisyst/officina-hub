<?php

namespace App\Services;

use App\Models\Documento;
use RuntimeException;

class SdiService
{
    /**
     * Invia un file XML firmato al SdI tramite SFTP.
     * Non implementato: richiede accreditamento formale AdE.
     * Vedere docs/sdi-diretto.md per i passi necessari.
     */
    public function inviaSftp(Documento $documento): void
    {
        if (! config('sdi.abilitato')) {
            throw new RuntimeException('Canale SdI non configurato. Vedere docs/sdi-diretto.md.');
        }
        // TODO: implementare dopo accreditamento AdE
    }

    /**
     * Firma digitalmente il file XML con il certificato qualificato.
     * Non implementato: richiede certificato CA accreditata (Aruba/InfoCert/Namirial).
     */
    public function firma(string $xml): string
    {
        if (! config('sdi.abilitato')) {
            throw new RuntimeException('Firma SdI non configurata. Vedere docs/sdi-diretto.md.');
        }
        // TODO: usare openssl_pkcs7_sign() con il certificato AdE
        return $xml;
    }

    /**
     * Polling della cartella di ricezione per le ricevute SdI.
     * Non implementato.
     */
    public function scaricaRicevute(): array
    {
        return [];
    }
}
