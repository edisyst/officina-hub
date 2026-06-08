<?php

namespace App\Services\LookupTarga;

interface LookupTargaContract
{
    /**
     * Ritorna i dati tecnici del veicolo o null se non trovato.
     *
     * @return array{marca: string, modello: string, versione: string|null,
     *               anno_immatricolazione: int|null, alimentazione: string|null,
     *               cilindrata: int|null, colore: string|null}|null
     */
    public function cerca(string $targa): ?array;
}
