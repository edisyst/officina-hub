<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
  .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
  .logo { max-height: 60px; }
  .officina-info { float: right; text-align: right; font-size: 10px; }
  .title { font-size: 18px; font-weight: bold; margin: 10px 0; color: #1a237e; }
  .subtitle { font-size: 12px; color: #555; }
  .section-title { font-weight: bold; font-size: 12px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 8px; text-transform: uppercase; color: #555; }
  .info-grid { width: 100%; }
  .info-grid td { padding: 2px 5px; }
  .info-grid td:first-child { font-weight: bold; width: 35%; }
  table.righe { width: 100%; border-collapse: collapse; margin-top: 5px; }
  table.righe th { background: #1a237e; color: white; padding: 5px; text-align: left; font-size: 10px; }
  table.righe td { padding: 4px 5px; border: 1px solid #ddd; }
  table.righe tr:nth-child(even) td { background: #f5f5f5; }
  .text-right { text-align: right; }
  .firma-area { border: 1px solid #ccc; height: 80px; margin-top: 5px; }
  .clausola { font-size: 9px; color: #777; border: 1px solid #ddd; padding: 8px; background: #fafafa; margin-top: 10px; }
  .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #ccc; padding: 5px 20px; font-size: 9px; color: #777; }
  .clearfix::after { content: ""; display: table; clear: both; }
  .validity-box { border: 2px solid #1a237e; padding: 8px; text-align: center; font-weight: bold; margin: 10px 0; }
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
  <div class="title">PREVENTIVO N. {{ $commessa->numero }}</div>
  <div class="subtitle">{{ $commessa->tipo->label() }} — Data: {{ now()->format('d/m/Y') }}</div>
</div>

<div class="validity-box">
  Il presente preventivo è valido 30 giorni dalla data di emissione
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
      <tr><td>KM al Ingresso:</td><td>{{ number_format($commessa->km_ingresso) }} km</td></tr>
      @endif
    </table>
  </div>
</div>

<!-- Descrizione lavori -->
<div class="section" style="margin-bottom:15px">
  <div class="section-title">Lavori Proposti</div>
  <p>{{ $commessa->descrizione_cliente }}</p>
</div>

<!-- Righe preventivo -->
<div class="section" style="margin-bottom:15px">
  <div class="section-title">Voci di Preventivo</div>
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
      <tr style="background:#f0f0f0">
        <td colspan="6" class="text-right" style="font-weight:bold;padding:5px">Imponibile:</td>
        <td class="text-right" style="font-weight:bold;border:1px solid #ccc">€ {{ number_format($imponibile, 2, ',', '.') }}</td>
      </tr>
      <tr style="background:#f0f0f0">
        <td colspan="6" class="text-right" style="font-weight:bold;padding:5px">IVA:</td>
        <td class="text-right" style="font-weight:bold;border:1px solid #ccc">€ {{ number_format($iva, 2, ',', '.') }}</td>
      </tr>
      <tr style="background:#1a237e;color:white">
        <td colspan="6" class="text-right" style="font-weight:bold;padding:5px">TOTALE PREVENTIVO:</td>
        <td class="text-right" style="font-weight:bold;border:1px solid #333">€ {{ number_format($imponibile + $iva, 2, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>
</div>

<!-- Clausola legale -->
@if(!empty($settings['clausola_preventivo']))
<div class="clausola">
  <strong>Nota legale:</strong> {{ $settings['clausola_preventivo'] }}
</div>
@endif

<!-- Firma accettazione -->
<div style="display:table;width:100%;margin-top:20px">
  <div style="display:table-cell;width:50%;padding-right:15px">
    <div class="section-title">Per accettazione — Il cliente</div>
    @if($commessa->firma_cliente_svg)
    {!! $commessa->firma_cliente_svg !!}
    @else
    <div class="firma-area"></div>
    @endif
    <p style="font-size:9px;color:#777;margin-top:3px">
      Data: ____/____/________ &nbsp;&nbsp;&nbsp; Firma: _________________________
    </p>
    <p style="font-size:9px;color:#777">
      Con la firma il cliente accetta il preventivo e autorizza l'esecuzione dei lavori.
    </p>
  </div>
  <div style="display:table-cell;width:50%;padding-left:15px">
    <div class="section-title">Per {{ $settings['officina_nome'] ?? 'l\'Officina' }}</div>
    <div class="firma-area"></div>
    <p style="font-size:9px;color:#777;margin-top:3px">Data: ____/____/________</p>
  </div>
</div>

<div class="footer">
  {{ $settings['officina_nome'] ?? 'Officina Hub' }} — Preventivo generato il {{ now()->format('d/m/Y H:i') }} — N. {{ $commessa->numero }}
</div>
</body>
</html>
