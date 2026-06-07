<?php

namespace App\Services;

use App\Models\Setting;

class EmailTemplateService
{
    /**
     * Compila un template email da settings sostituendo le variabili {{NOME}}.
     * Restituisce ['oggetto' => string, 'corpo' => string].
     */
    public function compila(string $templateKey, array $variabili): array
    {
        $template = Setting::get($templateKey, '');

        // La prima riga che inizia con "Oggetto:" diventa il soggetto
        $righe = explode("\n", $template);
        $oggetto = '';
        $corpoRighe = [];

        foreach ($righe as $i => $riga) {
            if ($i === 0 && str_starts_with(trim($riga), 'Oggetto:')) {
                $oggetto = trim(substr(trim($riga), 8));
            } else {
                $corpoRighe[] = $riga;
            }
        }

        // Rimuove righe vuote iniziali nel corpo
        while (count($corpoRighe) > 0 && trim($corpoRighe[0]) === '') {
            array_shift($corpoRighe);
        }

        $corpo = implode("\n", $corpoRighe);

        // Sostituisce le variabili {{NOME_VARIABILE}}
        foreach ($variabili as $chiave => $valore) {
            $oggetto = str_replace("{{{$chiave}}}", (string) $valore, $oggetto);
            $corpo   = str_replace("{{{$chiave}}}", (string) $valore, $corpo);
        }

        return compact('oggetto', 'corpo');
    }

    /** Variabili disponibili per i template commessa */
    public static function variabiliCommessa(): array
    {
        return [
            'NOME_CLIENTE', 'TARGA', 'MARCA_MODELLO', 'NUMERO_COMMESSA',
            'DATA_INGRESSO', 'DATA_USCITA_PREVISTA', 'DESCRIZIONE_CLIENTE',
            'TOTALE_COMMESSA', 'NOME_OFFICINA', 'EMAIL_OFFICINA', 'TELEFONO_OFFICINA',
        ];
    }

    /** Variabili disponibili per i template scadenza */
    public static function variabiliScadenza(): array
    {
        return [
            'NOME_CLIENTE', 'TARGA', 'MARCA_MODELLO',
            'TIPO_SCADENZA', 'DATA_SCADENZA',
            'NOME_OFFICINA', 'TELEFONO_OFFICINA',
        ];
    }
}
