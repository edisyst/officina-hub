<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; }
  .header { border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
  .logo { max-height: 60px; }
  .cortesia-banner { background: #fff3cd; border: 1px solid #ffc107; padding: 6px 10px; margin-bottom: 12px; font-size: 10px; color: #856404; text-align: center; }
  .info-table { width: 100%; margin-bottom: 12px; }
  .info-table td { padding: 3px 6px; vertical-align: top; }
  .info-table .label { font-weight: bold; color: #555; width: 35%; }
  .righe-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
  .righe-table th { background: #343a40; color: #fff; padding: 5px 8px; font-size: 10px; }
  .righe-table td { border-bottom: 1px solid #dee2e6; padding: 4px 8px; }
  .righe-table tfoot td { border-top: 2px solid #333; font-weight: bold; }
  .totali { float: right; width: 45%; }
  .totali table { width: 100%; border-collapse: collapse; }
  .totali td { padding: 3px 8px; }
  .totali .total-row { background: #343a40; color: #fff; font-weight: bold; font-size: 13px; }
  .footer { border-top: 1px solid #999; padding-top: 6px; margin-top: 20px; font-size: 9px; color: #666; text-align: center; }
  .clearfix::after { content: ''; display: block; clear: both; }
</style>
</head>
<body>

<div class="cortesia-banner">
  ⚠️ DOCUMENTO DI CORTESIA — Non ha valore fiscale ai sensi dell'art. 21 DPR 633/72
</div>

<div class="header">
  <table width="100%">
    <tr>
      <td width="60%">
        @if(!empty($settings['logo_path']) && file_exists(public_path($settings['logo_path'])))
          <img src="{{ public_path($settings['logo_path']) }}" class="logo"><br>
        @endif
        <strong style="font-size:14px">{{ $settings['officina_nome'] ?? '' }}</strong><br>
        {{ $settings['officina_via'] ?? $settings['officina_indirizzo'] ?? '' }}<br>
        @if(!empty($settings['officina_cap'])){{ $settings['officina_cap'] }} @endif
        {{ $settings['officina_citta'] ?? '' }}
        @if(!empty($settings['officina_provincia'])) ({{ $settings['officina_provincia'] }}) @endif<br>
        P.IVA: {{ $settings['officina_piva'] ?? '' }}
        @if(!empty($settings['officina_codice_fiscale'])) | C.F.: {{ $settings['officina_codice_fiscale'] }} @endif<br>
        {{ $settings['officina_telefono'] ?? '' }} — {{ $settings['officina_email'] ?? '' }}
      </td>
      <td width="40%" align="right" valign="top">
        <div style="font-size:18px; font-weight:bold; color:#333">
          {{ $documento->tipo->label() }}
        </div>
        <div style="font-size:13px; color:#555">N° {{ $documento->numero }}</div>
        <div>Data: {{ $documento->data_emissione->format('d/m/Y') }}</div>
        @if($documento->data_scadenza)
        <div>Scadenza: {{ $documento->data_scadenza->format('d/m/Y') }}</div>
        @endif
      </td>
    </tr>
  </table>
</div>

{{-- Dati cliente --}}
<table class="info-table">
  <tr>
    <td width="50%">
      <strong>Cliente</strong><br>
      {{ $documento->cliente->nome_completo }}<br>
      @if($documento->cliente->indirizzo) {{ $documento->cliente->indirizzo }}<br> @endif
      @if($documento->cliente->cap || $documento->cliente->citta)
        {{ $documento->cliente->cap }} {{ $documento->cliente->citta }}
        @if($documento->cliente->provincia) ({{ $documento->cliente->provincia }}) @endif<br>
      @endif
      @if($documento->cliente->partita_iva) P.IVA: {{ $documento->cliente->partita_iva }}<br> @endif
      @if($documento->cliente->codice_fiscale) C.F.: {{ $documento->cliente->codice_fiscale }} @endif
    </td>
    <td width="50%">
      @if($documento->metodo_pagamento)
      <strong>Metodo di pagamento</strong><br>
      {{ $documento->metodo_pagamento->label() }}<br>
      @if($documento->metodo_pagamento->value === 'bonifico' && !empty($settings['fatturapa_iban']))
        IBAN: {{ $settings['fatturapa_iban'] }}
      @endif
      @endif
    </td>
  </tr>
</table>

{{-- Righe --}}
<table class="righe-table">
  <thead>
    <tr>
      <th>#</th>
      <th>Descrizione</th>
      <th align="right">Qtà</th>
      <th align="right">Prezzo</th>
      <th align="right">Sconto</th>
      <th align="right">IVA %</th>
      <th align="right">Imponibile</th>
    </tr>
  </thead>
  <tbody>
    @foreach($documento->righe as $i => $riga)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td>{{ $riga->descrizione }}</td>
      <td align="right">{{ number_format((float)$riga->quantita, 2, ',', '.') }}</td>
      <td align="right">€ {{ number_format((float)$riga->prezzo_unitario, 2, ',', '.') }}</td>
      <td align="right">{{ $riga->sconto_percentuale > 0 ? $riga->sconto_percentuale.'%' : '—' }}</td>
      <td align="right">
        @if($riga->natura_iva) Esente {{ $riga->natura_iva }}
        @else {{ $riga->iva_percentuale }}%
        @endif
      </td>
      <td align="right">€ {{ number_format((float)$riga->imponibile_riga, 2, ',', '.') }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<div class="clearfix">
  <div class="totali">
    <table>
      <tr>
        <td>Imponibile</td>
        <td align="right">€ {{ number_format((float)$documento->imponibile, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <td>IVA</td>
        <td align="right">€ {{ number_format((float)$documento->iva_totale, 2, ',', '.') }}</td>
      </tr>
      <tr class="total-row">
        <td>TOTALE</td>
        <td align="right">€ {{ number_format((float)$documento->totale, 2, ',', '.') }}</td>
      </tr>
    </table>
  </div>
</div>

@if($documento->note)
<div style="margin-top:20px; padding:8px; border:1px solid #dee2e6; border-radius:4px; font-size:10px;">
  <strong>Note:</strong> {{ $documento->note }}
</div>
@endif

<div class="footer">
  Documento generato da Officina Hub il {{ now()->format('d/m/Y H:i') }} — Documento di cortesia privo di valore fiscale
</div>

</body>
</html>
