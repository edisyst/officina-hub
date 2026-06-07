<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; padding: 15px; }
    h1 { font-size: 14px; margin-bottom: 4px; }
    .subtitle { color: #666; font-size: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #3c8dbc; color: white; padding: 5px 8px; text-align: left; font-size: 9px; }
    td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 9px; }
    .text-right { text-align: right; }
    .badge-success { background: #28a745; color: white; padding: 1px 5px; border-radius: 3px; }
    .badge-warning { background: #ffc107; color: #333; padding: 1px 5px; border-radius: 3px; }
    .badge-danger  { background: #dc3545; color: white; padding: 1px 5px; border-radius: 3px; }
    .text-success { color: #28a745; }
    .text-danger  { color: #dc3545; }
    .grafico { margin-bottom: 15px; }
    .footer { margin-top: 20px; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
  </style>
</head>
<body>
  <h1>Report Produttività Meccanici</h1>
  <div class="subtitle">Periodo: {{ $periodoLabel }} ({{ $da }} — {{ $a }})</div>
  <div class="subtitle">Generato il {{ now()->format('d/m/Y H:i') }}</div>

  @if($graficoPng)
  <div class="grafico">
    <img src="{{ $graficoPng }}" style="max-width:100%;max-height:200px">
  </div>
  @endif

  <table>
    <thead>
      <tr>
        <th>Meccanico</th>
        <th class="text-right">Ore lav.</th>
        <th class="text-right">Ore fatt.</th>
        <th class="text-right">Efficienza</th>
        <th class="text-right">Ricavo (€)</th>
        <th class="text-right">Costo (€)</th>
        <th class="text-right">Margine (€)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($dati as $m)
      @php
        $effClass = $m['efficienza'] >= 80 ? 'badge-success' : ($m['efficienza'] >= 60 ? 'badge-warning' : 'badge-danger');
        $margClass = $m['margine'] >= 0 ? 'text-success' : 'text-danger';
      @endphp
      <tr>
        <td>{{ $m['nome'] }}</td>
        <td class="text-right">{{ number_format($m['ore_lavorate'], 1, ',', '') }}</td>
        <td class="text-right">{{ number_format($m['ore_fatturate'], 1, ',', '') }}</td>
        <td class="text-right">
          <span class="{{ $effClass }}">{{ $m['efficienza'] }}%</span>
        </td>
        <td class="text-right">{{ number_format($m['ricavo_generato'], 2, ',', '.') }}</td>
        <td class="text-right">{{ number_format($m['costo'], 2, ',', '.') }}</td>
        <td class="text-right {{ $margClass }}">
          <strong>{{ number_format($m['margine'], 2, ',', '.') }}</strong>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="footer">
    Officina Hub — Documento generato automaticamente — privo di valore legale
  </div>
</body>
</html>
