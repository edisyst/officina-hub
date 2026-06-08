<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Dati officina generali
            'officina_nome'             => 'La Mia Officina',
            'officina_indirizzo'        => 'Via Roma 1, 00100 Roma (RM)',
            'officina_via'              => 'Via Roma 1',
            'officina_cap'              => '00100',
            'officina_citta'            => 'Roma',
            'officina_provincia'        => 'RM',
            'officina_piva'             => '00000000000',
            'officina_codice_fiscale'   => '',
            'officina_telefono'         => '06 0000000',
            'officina_email'            => 'info@officinahub.local',
            'costo_orario_default'      => '45.00',
            'iva_default'               => '22',
            'clausola_preventivo'       => 'Il presente preventivo è valido 30 giorni dalla data di emissione. I prezzi sono IVA esclusa salvo diversa indicazione. Ai sensi del D.Lgs. 206/2005 e dell\'art. 2222 c.c., il presente documento costituisce proposta contrattuale.',
            // Parametri FatturaPA
            'fatturapa_regime_fiscale'  => 'RF01',
            'fatturapa_esigibilita_iva' => 'I',
            'fatturapa_iban'            => '',

            // Configurazione SMTP
            'email_smtp_host'           => '',
            'email_smtp_port'           => '587',
            'email_smtp_username'       => '',
            'email_smtp_password'       => '',
            'email_smtp_encryption'     => 'tls',
            'email_from_address'        => '',
            'email_from_name'           => 'La Mia Officina',
            'notifiche_email_abilitato' => '0',

            // Promemoria scadenze (giorni di anticipo)
            'promemoria_revisione_giorni'      => '30',
            'promemoria_tagliando_giorni'      => '30',
            'promemoria_assicurazione_giorni'  => '30',

            // Template email
            'template_email_accettazione' => implode("\n", [
                'Oggetto: Officina {{NOME_OFFICINA}} — Veicolo accettato [{{NUMERO_COMMESSA}}]',
                '',
                'Gentile {{NOME_CLIENTE}},',
                'confermiamo l\'accettazione del suo veicolo {{TARGA}} ({{MARCA_MODELLO}})',
                'in data {{DATA_INGRESSO}}.',
                'Numero commessa: {{NUMERO_COMMESSA}}',
                'Intervento richiesto: {{DESCRIZIONE_CLIENTE}}',
                'Data prevista di completamento: {{DATA_USCITA_PREVISTA}}',
                '',
                'Per informazioni può contattarci a {{EMAIL_OFFICINA}} oppure al {{TELEFONO_OFFICINA}}.',
                '',
                'Cordiali saluti,',
                '{{NOME_OFFICINA}}',
            ]),

            'template_email_completata' => implode("\n", [
                'Oggetto: Il suo veicolo {{TARGA}} è pronto per il ritiro',
                '',
                'Gentile {{NOME_CLIENTE}},',
                'i lavori sul suo veicolo {{TARGA}} sono stati completati.',
                'Può passare a ritirarlo presso la nostra officina.',
                '',
                'Importo da saldare: € {{TOTALE_COMMESSA}}',
                '',
                'Cordiali saluti,',
                '{{NOME_OFFICINA}}',
            ]),

            'template_email_consegnata' => implode("\n", [
                'Oggetto: Grazie per aver scelto {{NOME_OFFICINA}}',
                '',
                'Gentile {{NOME_CLIENTE}},',
                'grazie per aver affidato il suo veicolo {{TARGA}} alla nostra officina.',
                'La ringraziamo per la fiducia e Le auguriamo una buona guida.',
                '',
                'Cordiali saluti,',
                '{{NOME_OFFICINA}}',
            ]),

            'template_email_dvi' => implode("\n", [
                'Oggetto: Ispezione veicolo {{TARGA}} — sua risposta richiesta',
                '',
                'Gentile {{NOME_CLIENTE}},',
                'abbiamo completato l\'ispezione del suo veicolo {{TARGA}}.',
                '',
                'Clicchi sul link qui sotto per visualizzare il report fotografico',
                'e comunicarci quali interventi desidera far eseguire:',
                '',
                '{{LINK_DVI}}',
                '',
                'Il link sarà valido fino al {{DATA_SCADENZA}}.',
                '',
                '{{NOME_OFFICINA}} — {{TELEFONO_OFFICINA}}',
            ]),

            // Deposito pneumatici
            'deposito_pneumatici_abilitato'                   => '1',
            'etichetta_deposito_prefisso'                     => 'DEP',
            'notifica_cambio_stagionale_mese_estivo'          => '4',
            'notifica_cambio_stagionale_mese_invernale'       => '10',
            'etichetta_deposito_formato'                      => 'A6',

            'template_email_cambio_stagionale' => implode("\n", [
                'Oggetto: È il momento del cambio gomme — {{TARGA}}',
                '',
                'Gentile {{NOME_CLIENTE}},',
                'si avvicina la stagione del cambio pneumatici per il suo veicolo {{TARGA}}.',
                'Le ricordiamo che presso la nostra officina sono depositati i suoi pneumatici',
                '{{STAGIONE_DEPOSITO}} ({{MISURA}}).',
                '',
                'Chiami o risponda a questa email per fissare un appuntamento.',
                '{{NOME_OFFICINA}} — {{TELEFONO_OFFICINA}}',
            ]),

            // Lookup targa (disabilitato di default)
            'lookup_targa_abilitato'    => '0',
            'lookup_targa_provider'     => 'mock',
            'lookup_targa_api_key'      => '',
            'lookup_targa_timeout_ms'   => '3000',
            'lookup_targa_auto_search'  => '0',

            'template_email_richiamo_scadenza' => implode("\n", [
                'Oggetto: Promemoria {{TIPO_SCADENZA}} — {{TARGA}}',
                '',
                'Gentile {{NOME_CLIENTE}},',
                'La informiamo che per il suo veicolo {{TARGA}} ({{MARCA_MODELLO}})',
                'si avvicina la scadenza per: {{TIPO_SCADENZA}}.',
                'Data scadenza: {{DATA_SCADENZA}}',
                '',
                'La invitiamo a contattarci per fissare un appuntamento.',
                '',
                '{{NOME_OFFICINA}} — {{TELEFONO_OFFICINA}}',
            ]),
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
