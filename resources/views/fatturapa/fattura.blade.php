@php
/**
 * Template XML FatturaPA versione FPR12 (schema v1.2, specifiche AdE v1.9).
 * Variabili: $documento (Documento), $settings (array), $riepilogi (Collection).
 * Non usare layout o @extends: questo file produce XML puro.
 */
echo '<?xml version="1.0" encoding="UTF-8"?>';

$piva            = preg_replace('/[^0-9A-Z]/', '', strtoupper($settings['officina_piva'] ?? '00000000000'));
$progressivo     = str_pad($documento->progressivo, 5, '0', STR_PAD_LEFT);
$cliente         = $documento->cliente;
$isGiuridica     = $cliente->tipo->value === 'giuridica';
$codiceDestin    = $cliente->codice_destinatario_sdi ?: '0000000';
$regimeFiscale   = $settings['fatturapa_regime_fiscale'] ?? 'RF01';
$esigibilitaIVA  = $settings['fatturapa_esigibilita_iva'] ?? 'I';
$tipoDoc         = $documento->tipo->codiceFatturaPA();

// Indirizzo officina — può usare il campo dedicato o fallback sull'indirizzo completo
$viaOfficina     = $settings['officina_via'] ?? $settings['officina_indirizzo'] ?? '';
$capOfficina     = $settings['officina_cap'] ?? '00000';
$cittaOfficina   = $settings['officina_citta'] ?? '';
$provOfficina    = $settings['officina_provincia'] ?? null;
$cfOfficina      = $settings['officina_codice_fiscale'] ?? null;
$ibanOfficina    = $settings['fatturapa_iban'] ?? null;
@endphp
<p:FatturaElettronica versione="FPR12"
    xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.agenziaentrate.gov.it/wps/file/Nsilib/Nsi/Strumenti/Specifiche+tecniche/Specifiche+tecniche+versione+1.3.1/Schema+del+file+xml+FatturaPA+versione+1.3.1/Schema_del_file_xml_FatturaPA_versione_1.2.1.xsd">
  <FatturaElettronicaHeader>
    <DatiTrasmissione>
      <IdTrasmittente>
        <IdPaese>IT</IdPaese>
        <IdCodice>{!! e($piva) !!}</IdCodice>
      </IdTrasmittente>
      <ProgressivoInvio>{!! e($progressivo) !!}</ProgressivoInvio>
      <FormatoTrasmissione>FPR12</FormatoTrasmissione>
      <CodiceDestinatario>{!! e($codiceDestin) !!}</CodiceDestinatario>
@if(empty($cliente->codice_destinatario_sdi) && !empty($cliente->pec_sdi))
      <PECDestinatario>{!! e($cliente->pec_sdi) !!}</PECDestinatario>
@endif
    </DatiTrasmissione>
    <CedentePrestatore>
      <DatiAnagrafici>
        <IdFiscaleIVA>
          <IdPaese>IT</IdPaese>
          <IdCodice>{!! e($piva) !!}</IdCodice>
        </IdFiscaleIVA>
@if(!empty($cfOfficina))
        <CodiceFiscale>{!! e(strtoupper($cfOfficina)) !!}</CodiceFiscale>
@endif
        <Anagrafica>
          <Denominazione>{!! e($settings['officina_nome'] ?? 'Officina') !!}</Denominazione>
        </Anagrafica>
        <RegimeFiscale>{!! e($regimeFiscale) !!}</RegimeFiscale>
      </DatiAnagrafici>
      <Sede>
        <Indirizzo>{!! e($viaOfficina) !!}</Indirizzo>
        <CAP>{!! e($capOfficina) !!}</CAP>
        <Comune>{!! e($cittaOfficina) !!}</Comune>
@if(!empty($provOfficina))
        <Provincia>{!! e(strtoupper(substr($provOfficina, 0, 2))) !!}</Provincia>
@endif
        <Nazione>IT</Nazione>
      </Sede>
    </CedentePrestatore>
    <CessionarioCommittente>
      <DatiAnagrafici>
@if($isGiuridica && !empty($cliente->partita_iva))
        <IdFiscaleIVA>
          <IdPaese>IT</IdPaese>
          <IdCodice>{!! e(preg_replace('/[^0-9A-Z]/', '', strtoupper($cliente->partita_iva))) !!}</IdCodice>
        </IdFiscaleIVA>
@elseif(!empty($cliente->codice_fiscale))
        <CodiceFiscale>{!! e(strtoupper($cliente->codice_fiscale)) !!}</CodiceFiscale>
@endif
        <Anagrafica>
@if($isGiuridica)
          <Denominazione>{!! e($cliente->ragione_sociale) !!}</Denominazione>
@else
          <Nome>{!! e($cliente->nome) !!}</Nome>
          <Cognome>{!! e($cliente->cognome) !!}</Cognome>
@endif
        </Anagrafica>
      </DatiAnagrafici>
      <Sede>
        <Indirizzo>{!! e($cliente->indirizzo ?: 'ND') !!}</Indirizzo>
        <CAP>{!! e($cliente->cap ?: '00000') !!}</CAP>
        <Comune>{!! e($cliente->citta ?: 'ND') !!}</Comune>
@if(!empty($cliente->provincia))
        <Provincia>{!! e(strtoupper(substr($cliente->provincia, 0, 2))) !!}</Provincia>
@endif
        <Nazione>IT</Nazione>
      </Sede>
    </CessionarioCommittente>
  </FatturaElettronicaHeader>
  <FatturaElettronicaBody>
    <DatiGenerali>
      <DatiGeneraliDocumento>
        <TipoDocumento>{!! e($tipoDoc) !!}</TipoDocumento>
        <Divisa>EUR</Divisa>
        <Data>{!! e($documento->data_emissione->format('Y-m-d')) !!}</Data>
        <Numero>{!! e($documento->numero) !!}</Numero>
        <ImportoTotaleDocumento>{!! number_format((float)$documento->totale, 2, '.', '') !!}</ImportoTotaleDocumento>
@if(!empty($documento->note))
        <Causale>{!! e(mb_substr($documento->note, 0, 200)) !!}</Causale>
@endif
      </DatiGeneraliDocumento>
    </DatiGenerali>
    <DatiBeniServizi>
@foreach($documento->righe as $i => $riga)
      <DettaglioLinee>
        <NumeroLinea>{!! $i + 1 !!}</NumeroLinea>
        <Descrizione>{!! e($riga->descrizione) !!}</Descrizione>
        <Quantita>{!! number_format((float)$riga->quantita, 2, '.', '') !!}</Quantita>
        <UnitaMisura>{!! e(strtoupper($riga->unita_misura)) !!}</UnitaMisura>
        <PrezzoUnitario>{!! number_format((float)$riga->prezzo_unitario, 2, '.', '') !!}</PrezzoUnitario>
@if((float)$riga->sconto_percentuale > 0)
        <ScontoMaggiorazione>
          <Tipo>SC</Tipo>
          <Percentuale>{!! number_format((float)$riga->sconto_percentuale, 2, '.', '') !!}</Percentuale>
        </ScontoMaggiorazione>
@endif
        <PrezzoTotale>{!! number_format((float)$riga->imponibile_riga, 2, '.', '') !!}</PrezzoTotale>
@if(!empty($riga->natura_iva))
        <AliquotaIVA>0.00</AliquotaIVA>
        <Natura>{!! e($riga->natura_iva) !!}</Natura>
@else
        <AliquotaIVA>{!! number_format((float)$riga->iva_percentuale, 2, '.', '') !!}</AliquotaIVA>
@endif
      </DettaglioLinee>
@endforeach
@foreach($riepilogi as $riepilogo)
      <DatiRiepilogo>
        <AliquotaIVA>{!! number_format((float)$riepilogo['aliquota'], 2, '.', '') !!}</AliquotaIVA>
@if(!empty($riepilogo['natura']))
        <Natura>{!! e($riepilogo['natura']) !!}</Natura>
@endif
        <ImponibileImporto>{!! number_format((float)$riepilogo['imponibile'], 2, '.', '') !!}</ImponibileImporto>
        <Imposta>{!! number_format((float)$riepilogo['iva'], 2, '.', '') !!}</Imposta>
        <EsigibilitaIVA>{!! e($esigibilitaIVA) !!}</EsigibilitaIVA>
      </DatiRiepilogo>
@endforeach
    </DatiBeniServizi>
@if($documento->metodo_pagamento)
    <DatiPagamento>
      <CondizioniPagamento>TP02</CondizioniPagamento>
      <DettaglioPagamento>
        <ModalitaPagamento>{!! e($documento->metodo_pagamento->codiceModalitaFPA()) !!}</ModalitaPagamento>
@if($documento->data_scadenza)
        <DataScadenzaPagamento>{!! e($documento->data_scadenza->format('Y-m-d')) !!}</DataScadenzaPagamento>
@endif
        <ImportoPagamento>{!! number_format((float)$documento->totale, 2, '.', '') !!}</ImportoPagamento>
@if($documento->metodo_pagamento->value === 'bonifico' && !empty($ibanOfficina))
        <IBAN>{!! e($ibanOfficina) !!}</IBAN>
@endif
      </DettaglioPagamento>
    </DatiPagamento>
@endif
  </FatturaElettronicaBody>
</p:FatturaElettronica>
