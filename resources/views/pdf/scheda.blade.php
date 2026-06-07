<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
  .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
  .logo { max-height: 60px; }
  .officina-info { float: right; text-align: right; font-size: 10px; }
  .title { font-size: 18px; font-weight: bold; margin: 10px 0; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
  .badge-info { background: #17a2b8; color: #fff; }
  .badge-success { background: #28a745; color: #fff; }
  .badge-warning { background: #ffc107; color: #333; }
  .badge-primary { background: #007bff; color: #fff; }
  .section { margin-bottom: 15px; }
  .section-title { font-weight: bold; font-size: 12px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 8px; text-transform: uppercase; color: #555; }
  .info-grid { width: 100%; }
  .info-grid td { padding: 2px 5px; }
  .info-grid td:first-child { font-weight: bold; width: 35%; }
  table.righe { width: 100%; border-collapse: collapse; margin-top: 5px; }
  table.righe th { background: #f0f0f0; padding: 5px; text-align: left; border: 1px solid #ccc; font-size: 10px; }
  table.righe td { padding: 4px 5px; border: 1px solid #ddd; }
  table.righe tr.totale { font-weight: bold; background: #f9f9f9; }
  .firma-area { border: 1px solid #ccc; height: 80px; margin-top: 5px; }
  .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #ccc; padding: 5px 20px; font-size: 9px; color: #777; }
  .clearfix::after { content: ""; display: table; clear: both; }
  .text-right { text-align: right; }
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
  <div class="title">SCHEDA DI LAVORO N. {{ $commessa->numero }}</div>
  <span class="badge badge-{{ $commessa->stato->badgeClass() }}">{{ $commessa->stato->label() }}</span>
  <span class="badge badge-info" style="margin-left:5px">{{ $commessa->tipo->label() }}</span>
</div>

<!-- Dati cliente e veicolo -->
<div style="display:table;width:100%;margin-bottom:15px">
  <div style="display:table-cell;width:50%;padding-right:10px">
    <div class="section-title">Cliente</div>
    <table class="info-grid">
      <tr><td>Nome/Ragione Sociale:</td><td>{{ $commessa->cliente->nome_completo }}</td></tr>
      @if($commessa->cliente->codice_fiscale)
      <tr><td>Codice Fiscale:</td><td>{{ $commessa->cliente->codice_fiscale }}</td></tr>
      @endif
      @if($commessa->cliente->partita_iva)
      <tr><td>Partita IVA:</td><td>{{ $commessa->cliente->partita_iva }}</td></tr>
      @endif
      @if($commessa->cliente->telefono)
      <tr><td>Telefono:</td><td>{{ $commessa->cliente->telefono }}</td></tr>
      @endif
      @if($commessa->cliente->indirizzo)
      <tr><td>Indirizzo:</td><td>{{ $commessa->cliente->indirizzo }}, {{ $commessa->cliente->cap }} {{ $commessa->cliente->citta }} ({{ $commessa->cliente->provincia }})</td></tr>
      @endif
    </table>
  </div>
  <div style="display:table-cell;width:50%;padding-left:10px">
    <div class="section-title">Veicolo</div>
    <table class="info-grid">
      <tr><td>Targa:</td><td>{{ $commessa->veicolo->targa ?? 'N/D' }}</td></tr>
      <tr><td>Marca/Modello:</td><td>{{ $commessa->veicolo->marca }} {{ $commessa->veicolo->modello }} {{ $commessa->veicolo->versione }}</td></tr>
      @if($commessa->veicolo->vin)
      <tr><td>Telaio (VIN):</td><td>{{ $commessa->veicolo->vin }}</td></tr>
      @endif
      <tr><td>Alimentazione:</td><td>{{ $commessa->veicolo->alimentazione->label() }}</td></tr>
      @if($commessa->km_ingresso)
      <tr><td>KM Ingresso:</td><td>{{ number_format($commessa->km_ingresso) }} km</td></tr>
      @endif
      <tr><td>Data Ingresso:</td><td>{{ $commessa->data_ingresso->format('d/m/Y H:i') }}</td></tr>
      @if($commessa->data_uscita_prevista)
      <tr><td>Uscita Prevista:</td><td>{{ $commessa->data_uscita_prevista->format('d/m/Y') }}</td></tr>
      @endif
    </table>
  </div>
</div>

<!-- Descrizione cliente -->
<div class="section">
  <div class="section-title">Descrizione del Problema (parole del cliente)</div>
  <p style="background:#fffde7;padding:8px;border:1px solid #f0e68c;border-radius:3px">
    {{ $commessa->descrizione_cliente }}
  </p>
</div>

@if($commessa->diagnosi_tecnica)
<div class="section">
  <div class="section-title">Diagnosi Tecnica</div>
  <p>{{ $commessa->diagnosi_tecnica }}</p>
</div>
@endif

<!-- Righe di lavorazione -->
<div class="section">
  <div class="section-title">Lavorazioni e Materiali</div>
  <table class="righe">
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Descrizione</th>
        <th class="text-right">Qtà</th>
        <th class="text-right">Prezzo Unit.</th>
        <th class="text-right">Sconto</th>
        <th class="text-right">IVA</th>
        <th class="text-right">Totale</th>
      </tr>
    </thead>
    <tbody>
      @foreach($commessa->righe as $riga)
      <tr>
        <td>{{ $riga->tipo->label() }}</td>
        <td>{{ $riga->descrizione }}</td>
        <td class="text-right">{{ $riga->tipo->value !== 'nota' ? $riga->quantita : '' }}</td>
        <td class="text-right">{{ $riga->tipo->value !== 'nota' ? '€ '.number_format($riga->prezzo_unitario, 2, ',', '.') : '' }}</td>
        <td class="text-right">{{ $riga->sconto_percentuale > 0 ? $riga->sconto_percentuale.'%' : '' }}</td>
        <td class="text-right">{{ $riga->tipo->value !== 'nota' ? $riga->iva_percentuale.'%' : '' }}</td>
        <td class="text-right">{{ $riga->tipo->value !== 'nota' ? '€ '.number_format($riga->imponibile, 2, ',', '.') : '' }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      @php
        $imponibile = $commessa->righe->sum(fn($r) => $r->imponibile);
        $iva = $commessa->righe->sum(fn($r) => $r->iva);
      @endphp
      <tr><td colspan="6" class="text-right" style="border:none;padding-top:5px">Imponibile:</td><td class="text-right" style="border:1px solid #ccc">€ {{ number_format($imponibile, 2, ',', '.') }}</td></tr>
      <tr><td colspan="6" class="text-right" style="border:none">IVA:</td><td class="text-right" style="border:1px solid #ccc">€ {{ number_format($iva, 2, ',', '.') }}</td></tr>
      <tr class="totale"><td colspan="6" class="text-right" style="border:none">TOTALE:</td><td class="text-right" style="border:2px solid #333">€ {{ number_format($imponibile + $iva, 2, ',', '.') }}</td></tr>
    </tfoot>
  </table>
</div>

<!-- Firma cliente -->
<div style="display:table;width:100%;margin-top:20px">
  <div style="display:table-cell;width:50%;padding-right:10px">
    <div class="section-title">Firma del Cliente</div>
    @if($commessa->firma_cliente_svg)
    {!! $commessa->firma_cliente_svg !!}
    @else
    <div class="firma-area"></div>
    <p style="font-size:9px;color:#777">Data e firma del cliente per accettazione</p>
    @endif
  </div>
  @if($commessa->firma_consegna_svg)
  <div style="display:table-cell;width:50%;padding-left:10px">
    <div class="section-title">Firma alla Consegna</div>
    {!! $commessa->firma_consegna_svg !!}
  </div>
  @endif
</div>

<div class="footer">
  {{ $settings['officina_nome'] ?? 'Officina Hub' }} — Scheda generata il {{ now()->format('d/m/Y H:i') }} — N. {{ $commessa->numero }}
</div>
</body>
</html>
