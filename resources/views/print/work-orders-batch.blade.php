<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Stampa commesse</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 0; }
  .page { padding: 20px; page-break-after: always; }
  .page:last-child { page-break-after: auto; }
  .header { border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; }
  .title { font-size: 16px; font-weight: bold; }
  .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: bold; background: #ccc; }
  table.righe { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
  table.righe th { background: #f0f0f0; padding: 4px; border: 1px solid #ccc; text-align: left; }
  table.righe td { padding: 3px 4px; border: 1px solid #ddd; }
  .info-row { display: flex; gap: 20px; margin-bottom: 8px; }
  .info-block label { font-weight: bold; display: block; font-size: 9px; text-transform: uppercase; color: #777; }
  .print-btn { position: fixed; top: 10px; right: 10px; padding: 8px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
  @media print { .print-btn { display: none; } }
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨 Stampa</button>

@forelse($commesse as $c)
<div class="page">
  <div class="header">
    <div>
      <div class="title">{{ $c->numero }} &nbsp;<span class="badge">{{ $c->stato->label() }}</span></div>
      <div>{{ $c->tipo->label() }}</div>
    </div>
    <div style="text-align:right;font-size:10px">
      <strong>{{ setting('officina_nome', 'Officina Hub') }}</strong><br>
      {{ setting('officina_indirizzo', '') }}<br>
      {{ setting('officina_telefono', '') }}
    </div>
  </div>

  <div class="info-row">
    <div class="info-block">
      <label>Cliente</label>
      {{ $c->cliente->nome_completo }}
    </div>
    <div class="info-block">
      <label>Veicolo</label>
      {{ $c->veicolo->targa ?? '-' }} &mdash; {{ $c->veicolo->marca }} {{ $c->veicolo->modello }}
    </div>
    <div class="info-block">
      <label>Ingresso</label>
      {{ $c->data_ingresso->format('d/m/Y') }}
    </div>
    @if($c->data_uscita_prevista)
    <div class="info-block">
      <label>Uscita prevista</label>
      {{ $c->data_uscita_prevista->format('d/m/Y') }}
    </div>
    @endif
  </div>

  @if($c->descrizione_cliente)
  <p style="margin:0 0 8px"><strong>Descrizione cliente:</strong> {{ $c->descrizione_cliente }}</p>
  @endif

  <table class="righe">
    <thead>
      <tr>
        <th style="width:40%">Descrizione</th>
        <th style="width:10%">Tipo</th>
        <th style="width:8%" class="text-right">Qtà</th>
        <th style="width:12%" class="text-right">Prezzo u.</th>
        <th style="width:8%" class="text-right">Sc.%</th>
        <th style="width:12%" class="text-right">Totale</th>
      </tr>
    </thead>
    <tbody>
      @foreach($c->righe as $r)
      <tr>
        <td>{{ $r->descrizione }}</td>
        <td>{{ $r->tipo->value }}</td>
        <td style="text-align:right">{{ $r->tipo->value !== 'nota' ? number_format((float)$r->quantita, 2, ',', '.') : '' }}</td>
        <td style="text-align:right">{{ $r->tipo->value !== 'nota' ? '€ ' . number_format((float)$r->prezzo_unitario, 2, ',', '.') : '' }}</td>
        <td style="text-align:right">{{ $r->sconto_percentuale > 0 ? number_format((float)$r->sconto_percentuale, 1, ',', '.').'%' : '' }}</td>
        <td style="text-align:right">{{ $r->tipo->value !== 'nota' ? '€ ' . number_format($r->totale_cliente, 2, ',', '.') : '' }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr style="font-weight:bold">
        <td colspan="5" style="text-align:right">Totale</td>
        <td style="text-align:right">€ {{ number_format($c->righe->sum('totale_cliente'), 2, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>
</div>
@empty
<p style="padding:20px">Nessuna commessa da stampare.</p>
@endforelse

</body>
</html>
