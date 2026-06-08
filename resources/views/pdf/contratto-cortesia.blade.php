<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
  .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
  .officina-info { float: right; text-align: right; font-size: 10px; }
  .logo { max-height: 60px; }
  .title { font-size: 16px; font-weight: bold; margin: 10px 0 5px; text-transform: uppercase; }
  .subtitle { font-size: 10px; color: #666; margin-bottom: 15px; }
  .section { margin-bottom: 15px; }
  .section-title { font-weight: bold; font-size: 12px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 8px; text-transform: uppercase; color: #555; }
  .grid { width: 100%; border-collapse: collapse; }
  .grid td { padding: 3px 5px; border-bottom: 1px solid #eee; font-size: 10px; }
  .grid td.label { font-weight: bold; width: 40%; color: #555; }
  .clausole { font-size: 9px; line-height: 1.5; color: #444; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 3px; }
  .clausole p { margin: 0 0 5px; }
  .firma-row { width: 100%; margin-top: 20px; }
  .firma-box { width: 45%; display: inline-block; vertical-align: top; }
  .firma-area { border: 1px solid #ccc; height: 80px; margin-top: 5px; background: #fafafa; }
  .firma-svg { width: 100%; height: 80px; }
  .clearfix::after { content: ""; display: table; clear: both; }
  .avviso-fiscale { font-size: 8px; color: #999; text-align: center; border-top: 1px solid #ccc; padding-top: 5px; margin-top: 15px; }
  table.firma-table { width: 100%; }
  table.firma-table td { width: 50%; vertical-align: top; padding: 0 10px; }
</style>
</head>
<body>

<div class="header clearfix">
  <div class="officina-info">
    <strong>{{ $settings['officina_nome'] ?? 'Officina Hub' }}</strong><br>
    {{ $settings['officina_indirizzo'] ?? '' }}<br>
    P.IVA: {{ $settings['officina_piva'] ?? '' }}<br>
    Tel: {{ $settings['officina_telefono'] ?? '' }}<br>
    {{ $settings['officina_email'] ?? '' }}
  </div>
  @if(file_exists(public_path('images/logo.png')))
  <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
  @endif
</div>

<div class="clearfix" style="margin-bottom:10px">
  <div class="title">Contratto di Comodato d'Uso Gratuito</div>
  <div class="subtitle">Art. 1803 e ss. Codice Civile — Veicolo di cortesia</div>
</div>

{{-- Dati veicolo di cortesia --}}
<div class="section">
  <div class="section-title">Veicolo di cortesia</div>
  <table class="grid">
    <tr><td class="label">Targa</td><td>{{ $prestito->veicolo->targa }}</td><td class="label">Tipo</td><td>{{ ucfirst($prestito->veicolo->tipo) }}</td></tr>
    <tr><td class="label">Marca / Modello</td><td>{{ $prestito->veicolo->marca }} {{ $prestito->veicolo->modello }}</td><td class="label">Anno</td><td>{{ $prestito->veicolo->anno ?? '—' }}</td></tr>
    <tr><td class="label">Colore</td><td>{{ $prestito->veicolo->colore ?? '—' }}</td><td class="label">Carburante</td><td>{{ ucfirst($prestito->veicolo->carburante_tipo) }}</td></tr>
    <tr><td class="label">Km alla consegna</td><td>{{ number_format($prestito->km_consegna, 0, ',', '.') }} km</td><td class="label">Carb. alla consegna</td><td>{{ $prestito->carburante_consegna }}%</td></tr>
  </table>
</div>

{{-- Dati comodatario --}}
<div class="section">
  <div class="section-title">Comodatario (Cliente)</div>
  <table class="grid">
    <tr><td class="label">Nome e Cognome / Ragione sociale</td><td colspan="3">{{ $prestito->cliente->nome_completo }}</td></tr>
    <tr><td class="label">Codice Fiscale</td><td>{{ $prestito->cliente->codice_fiscale ?? '—' }}</td><td class="label">N. Patente</td><td>{{ $prestito->cliente->patente_numero ?? '—' }}</td></tr>
    <tr><td class="label">Scadenza Patente</td><td>{{ $prestito->cliente->patente_scadenza ? $prestito->cliente->patente_scadenza->format('d/m/Y') : '—' }}</td><td class="label">Telefono</td><td>{{ $prestito->cliente->telefono ?? '—' }}</td></tr>
    <tr><td class="label">Indirizzo</td><td colspan="3">{{ $prestito->cliente->indirizzo ?? '—' }}, {{ $prestito->cliente->cap ?? '' }} {{ $prestito->cliente->citta ?? '' }} ({{ $prestito->cliente->provincia ?? '' }})</td></tr>
  </table>
</div>

{{-- Periodo --}}
<div class="section">
  <div class="section-title">Periodo di comodato</div>
  <table class="grid">
    <tr><td class="label">Data e ora consegna</td><td>{{ $prestito->data_consegna->format('d/m/Y H:i') }}</td><td class="label">Data rientro prevista</td><td>{{ $prestito->data_rientro_prevista->format('d/m/Y') }}</td></tr>
    @if($prestito->cauzione_importo > 0)
    <tr><td class="label">Cauzione versata</td><td>€ {{ number_format($prestito->cauzione_importo, 2, ',', '.') }}</td><td class="label">Cauzione pagata</td><td>{{ $prestito->cauzione_pagata ? 'Sì' : 'No' }}</td></tr>
    @endif
    @if($prestito->commessa)
    <tr><td class="label">Commessa di riferimento</td><td colspan="3">{{ $prestito->commessa->numero }}</td></tr>
    @endif
  </table>
</div>

{{-- Clausole legali --}}
<div class="section">
  <div class="section-title">Condizioni del Contratto di Comodato</div>
  <div class="clausole">
    <p><strong>1. Custodia.</strong> Il comodatario è responsabile della custodia del veicolo durante l'intero periodo di comodato e si impegna a conservarlo con la diligenza del buon padre di famiglia (art. 1804 c.c.).</p>
    <p><strong>2. Utilizzo.</strong> Il veicolo deve essere utilizzato esclusivamente per uso personale del comodatario, conformemente alla sua natura e destinazione. Il veicolo <strong>non può essere sublocato né ceduto a terzi</strong>, a titolo oneroso o gratuito, pena la risoluzione immediata del contratto.</p>
    <p><strong>3. Restituzione.</strong> Il veicolo deve essere restituito entro la data indicata, nello stesso stato in cui è stato consegnato, salvo il normale deterioramento d'uso. In caso di ritardo nel rientro l'officina si riserva di richiedere un risarcimento proporzionale.</p>
    <p><strong>4. Responsabilità verso terzi.</strong> Il comodatario è interamente responsabile dei danni causati a terzi e/o a cose durante l'utilizzo del veicolo, incluse eventuali sanzioni amministrative (infrazioni al codice della strada, ZTL, sosta). Sarà altresì responsabile degli atti vandalici subiti dal veicolo lasciato incustodito.</p>
    <p><strong>5. Spese.</strong> Il carburante e tutte le spese di esercizio sono a carico del comodatario. Il veicolo dovrà essere riconsegnato con un livello di carburante non inferiore a quello della consegna.</p>
    <p><strong>6. Sinistri.</strong> In caso di sinistro, anche minore, il comodatario è obbligato a darne immediata comunicazione all'officina, a non abbandonare il veicolo e a compilare la constatazione amichevole (CID). Qualsiasi intervento di terzi (carrozzeria, meccanico) dovrà essere preventivamente autorizzato dall'officina.</p>
    <p><strong>7. Limitazioni.</strong> È vietato l'uso del veicolo per competizioni, prove cronometrate, taxi o qualsiasi uso improprio. La guida del veicolo è consentita solo al comodatario con patente valida.</p>
    <p><strong>8. Legge applicabile.</strong> Il presente contratto è regolato dalla legge italiana. Per qualsiasi controversia è competente in via esclusiva il Tribunale del luogo in cui ha sede l'officina.</p>
  </div>
</div>

{{-- Firme --}}
<table class="firma-table" style="margin-top:20px">
  <tr>
    <td style="text-align:center;">
      <strong>Comodante — {{ $settings['officina_nome'] ?? 'Officina' }}</strong><br>
      Firma: {{ $prestito->utenteConsegna->name }}<br>
      <div style="border: 1px solid #ccc; height: 60px; margin-top: 5px; background:#fafafa;"></div>
    </td>
    <td style="text-align:center;">
      <strong>Comodatario — {{ $prestito->cliente->nome_completo }}</strong><br>
      Firma:<br>
      @if($prestito->firma_consegna_svg)
        {!! $prestito->firma_consegna_svg !!}
      @else
        <div style="border: 1px solid #ccc; height: 60px; margin-top: 5px; background:#fafafa;"></div>
      @endif
    </td>
  </tr>
</table>

<div class="avviso-fiscale">
  Documento generato automaticamente — privo di valore fiscale. Data: {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
