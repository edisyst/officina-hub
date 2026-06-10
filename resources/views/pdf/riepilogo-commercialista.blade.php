<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 0; }
  .header { border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px; }
  .watermark { background: #fff3cd; border: 2px solid #ffc107; padding: 6px 10px; margin-bottom: 10px; font-size: 10px; color: #856404; text-align: center; font-weight: bold; }
  h2 { font-size: 13px; margin: 10px 0 4px; border-bottom: 1px solid #ccc; padding-bottom: 3px; color: #333; }
  h3 { font-size: 11px; margin: 8px 0 3px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 9px; }
  th { background: #343a40; color: #fff; padding: 4px 6px; text-align: left; }
  td { border-bottom: 1px solid #dee2e6; padding: 3px 6px; }
  tfoot td { border-top: 2px solid #333; font-weight: bold; background: #f8f9fa; }
  .text-right { text-align: right; }
  .badge-entrata { background: #28a745; color: #fff; padding: 1px 4px; border-radius: 3px; }
  .badge-uscita  { background: #dc3545; color: #fff; padding: 1px 4px; border-radius: 3px; }
  .totali-box { display: inline-block; border: 1px solid #dee2e6; padding: 6px 12px; margin-right: 10px; border-radius: 4px; }
  .totali-box .label { font-size: 9px; color: #666; }
  .totali-box .value { font-size: 13px; font-weight: bold; }
  .footer { border-top: 1px solid #999; padding-top: 5px; margin-top: 15px; font-size: 8px; color: #666; text-align: center; }
  .page-break { page-break-after: always; }
</style>
</head>
<body>

<div class="watermark">
  NON HA VALORE FISCALE — Documento operativo ad uso interno — Generato il {{ now()->format('d/m/Y H:i') }}
</div>

<div class="header">
  <table>
    <tr>
      <td style="width:60%">
        <strong style="font-size:13px">{{ $settings['officina_nome'] ?? 'Officina' }}</strong><br>
        {{ $settings['officina_indirizzo'] ?? '' }}<br>
        <small>P.IVA: {{ $settings['officina_piva'] ?? '' }} — Tel: {{ $settings['officina_telefono'] ?? '' }}</small>
      </td>
      <td style="width:40%; text-align:right">
        <strong>RIEPILOGO PERIODICO</strong><br>
        Periodo: {{ \Carbon\Carbon::parse($dal)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($al)->format('d/m/Y') }}<br>
        <small>Elaborazione: {{ now()->format('d/m/Y H:i') }}</small>
      </td>
    </tr>
  </table>
</div>

<h2>1. Riepilogo fatture emesse</h2>
<table>
  <thead>
    <tr>
      <th>Aliquota / Natura IVA</th>
      <th class="text-right">Imponibile</th>
      <th class="text-right">IVA</th>
      <th class="text-right">Totale</th>
    </tr>
  </thead>
  <tbody>
    @forelse($perAliquota as $aliquota => $riga)
    <tr>
      <td>{{ $aliquota }}</td>
      <td class="text-right">€ {{ number_format($riga['imponibile'], 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format($riga['iva'], 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format($riga['totale'], 2, ',', '.') }}</td>
    </tr>
    @empty
    <tr><td colspan="4" style="text-align:center;color:#999">Nessuna fattura nel periodo.</td></tr>
    @endforelse
  </tbody>
  @if($perAliquota->isNotEmpty())
  <tfoot>
    <tr>
      <td>TOTALE</td>
      <td class="text-right">€ {{ number_format($perAliquota->sum('imponibile'), 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format($perAliquota->sum('iva'), 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format($totaleDocumenti, 2, ',', '.') }}</td>
    </tr>
  </tfoot>
  @endif
</table>

<h2>2. Incassi e insoluti</h2>
<table>
  <tbody>
    <tr>
      <td style="width:50%">Totale fatturato nel periodo</td>
      <td class="text-right">€ {{ number_format($totaleDocumenti, 2, ',', '.') }}</td>
    </tr>
    <tr>
      <td>Incassato nel periodo</td>
      <td class="text-right">€ {{ number_format($totalePagato, 2, ',', '.') }}</td>
    </tr>
    <tr style="font-weight:bold; background:#f8f9fa">
      <td>Insoluto residuo</td>
      <td class="text-right">€ {{ number_format($insoluto, 2, ',', '.') }}</td>
    </tr>
  </tbody>
</table>

<h2>3. Prima nota del periodo</h2>
@if($movimentiPrimaNota->isNotEmpty())
<table>
  <thead>
    <tr>
      <th>Data</th>
      <th>Causale</th>
      <th>Tipo</th>
      <th>Metodo</th>
      <th class="text-right">Importo</th>
    </tr>
  </thead>
  <tbody>
    @foreach($movimentiPrimaNota as $m)
    <tr>
      <td>{{ $m->data->format('d/m/Y') }}</td>
      <td>{{ $m->causale }}</td>
      <td>
        @if($m->tipo->value === 'entrata')
          <span class="badge-entrata">Entrata</span>
        @else
          <span class="badge-uscita">Uscita</span>
        @endif
      </td>
      <td>{{ $m->metodo->label() }}</td>
      <td class="text-right">€ {{ number_format((float)$m->importo, 2, ',', '.') }}</td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4" class="text-right">Totale entrate</td>
      <td class="text-right">€ {{ number_format($movimentiPrimaNota->where('tipo.value', 'entrata')->sum(fn($m) => (float)$m->importo), 2, ',', '.') }}</td>
    </tr>
    <tr>
      <td colspan="4" class="text-right">Totale uscite</td>
      <td class="text-right">€ {{ number_format($movimentiPrimaNota->where('tipo.value', 'uscita')->sum(fn($m) => (float)$m->importo), 2, ',', '.') }}</td>
    </tr>
  </tfoot>
</table>
@else
<p style="color:#999; text-align:center">Nessun movimento registrato nel periodo.</p>
@endif

<div class="page-break"></div>

<h2>4. Registro IVA vendite del periodo</h2>
@if($registroIva->isNotEmpty())
<table>
  <thead>
    <tr>
      <th>Data</th>
      <th>Numero</th>
      <th>Cliente</th>
      <th class="text-right">Aliq. %</th>
      <th class="text-right">Imponibile</th>
      <th class="text-right">IVA</th>
      <th class="text-right">Totale</th>
    </tr>
  </thead>
  <tbody>
    @foreach($registroIva as $r)
    <tr>
      <td>{{ $r->data_registrazione->format('d/m/Y') }}</td>
      <td>{{ $r->numero_documento }}</td>
      <td>{{ $r->cliente_fornitore }}</td>
      <td class="text-right">{{ $r->natura_iva ?: ((int)$r->aliquota_iva . '%') }}</td>
      <td class="text-right">€ {{ number_format((float)$r->imponibile, 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format((float)$r->iva, 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format((float)$r->totale, 2, ',', '.') }}</td>
    </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4" class="text-right">Totali</td>
      <td class="text-right">€ {{ number_format($registroIva->sum(fn($r) => (float)$r->imponibile), 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format($registroIva->sum(fn($r) => (float)$r->iva), 2, ',', '.') }}</td>
      <td class="text-right">€ {{ number_format($registroIva->sum(fn($r) => (float)$r->totale), 2, ',', '.') }}</td>
    </tr>
  </tfoot>
</table>
@else
<p style="color:#999; text-align:center">Nessuna voce nel registro IVA per il periodo selezionato.</p>
@endif

<div class="footer">
  {{ $settings['officina_nome'] ?? '' }} — Documento generato automaticamente da Officina Hub — NON HA VALORE FISCALE
</div>

</body>
</html>
