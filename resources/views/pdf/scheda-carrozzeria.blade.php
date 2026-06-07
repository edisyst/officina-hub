<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 20px; }
  h1 { font-size: 18px; margin: 0 0 4px; }
  h2 { font-size: 13px; margin: 12px 0 4px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
  h3 { font-size: 11px; margin: 8px 0 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  th, td { border: 1px solid #ddd; padding: 4px 6px; font-size: 9px; }
  th { background: #f5f5f5; font-weight: bold; }
  .header { display: flex; justify-content: space-between; margin-bottom: 16px; }
  .officina { font-size: 11px; }
  .doc-id { text-align: right; font-size: 11px; }
  .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
  .badge-secondary { background: #6c757d; color: #fff; }
  .badge-warning { background: #ffc107; color: #212529; }
  .badge-primary { background: #007bff; color: #fff; }
  .badge-success { background: #28a745; color: #fff; }
  .badge-danger { background: #dc3545; color: #fff; }
  .section { margin-bottom: 12px; }
  .row { display: flex; gap: 12px; }
  .col { flex: 1; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .text-muted { color: #6c757d; }
  .font-bold { font-weight: bold; }
  .zona-danno { fill: #fff3cd; stroke: #ffc107; }
  .zona-ok { fill: #f1f3f4; stroke: #dee2e6; }
  .zona-grave { fill: #f8d7da; stroke: #dc3545; }
  .foto-thumb { width: 80px; height: 60px; object-fit: cover; }
  .disclaimer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 8px; color: #999; text-align: center; }
</style>
</head>
<body>

{{-- INTESTAZIONE --}}
<div class="header">
  <div class="officina">
    <strong style="font-size:14px">{{ $settings['nome_officina'] ?? 'Officina Hub' }}</strong><br>
    {{ $settings['indirizzo'] ?? '' }}
    @if($settings['citta'] ?? false) — {{ $settings['citta'] }} @endif
    @if($settings['telefono'] ?? false)<br>Tel: {{ $settings['telefono'] }} @endif
    @if($settings['email'] ?? false) — {{ $settings['email'] }} @endif
    @if($settings['partita_iva'] ?? false)<br>P.IVA: {{ $settings['partita_iva'] }} @endif
  </div>
  <div class="doc-id">
    <strong>SCHEDA CARROZZERIA</strong><br>
    Commessa: <strong>{{ $commessa->numero }}</strong><br>
    Data: {{ now()->format('d/m/Y') }}<br>
    <span class="badge badge-secondary">{{ $commessa->tipo->label() }}</span>
    @if($commessa->stato_carrozzeria)
    <span class="badge badge-primary ml-1">{{ $commessa->stato_carrozzeria->label() }}</span>
    @endif
  </div>
</div>

{{-- DATI CLIENTE E VEICOLO --}}
<div class="row section">
  <div class="col">
    <h2>Cliente</h2>
    <strong>{{ $commessa->cliente->nome_completo }}</strong><br>
    @if($commessa->cliente->telefono) Tel: {{ $commessa->cliente->telefono }}<br> @endif
    @if($commessa->cliente->email) {{ $commessa->cliente->email }}<br> @endif
    @if($commessa->cliente->codice_fiscale) CF: {{ $commessa->cliente->codice_fiscale }} @endif
  </div>
  <div class="col">
    <h2>Veicolo</h2>
    <strong>{{ $commessa->veicolo->marca }} {{ $commessa->veicolo->modello }}</strong><br>
    Targa: <strong>{{ $commessa->veicolo->targa ?? 'N/D' }}</strong><br>
    @if($commessa->veicolo->colore) Colore: {{ $commessa->veicolo->colore }}<br> @endif
    @if($commessa->km_ingresso) KM Ingresso: {{ number_format($commessa->km_ingresso) }} @endif
    @if($commessa->km_uscita) — KM Uscita: {{ number_format($commessa->km_uscita) }} @endif
  </div>
  <div class="col">
    <h2>Date</h2>
    Ingresso: <strong>{{ $commessa->data_ingresso->format('d/m/Y H:i') }}</strong><br>
    @if($commessa->data_uscita_prevista) Uscita prev.: {{ $commessa->data_uscita_prevista->format('d/m/Y') }}<br> @endif
    @if($commessa->data_consegna) Consegnato: {{ $commessa->data_consegna->format('d/m/Y') }} @endif
  </div>
</div>

{{-- SINISTRO --}}
@if($commessa->sinistro)
@php $sinistro = $commessa->sinistro; $perizia = $sinistro->perizia; @endphp
<div class="section">
  <h2>Dati Sinistro</h2>
  <table>
    <tr>
      <th width="25%">Compagnia</th><td>{{ $sinistro->compagniaAssicurativa?->nome ?? '—' }}</td>
      <th width="25%">N° Sinistro</th><td>{{ $sinistro->numero_sinistro ?? '—' }}</td>
    </tr>
    <tr>
      <th>Tipo</th><td>{{ $sinistro->tipo_sinistro->label() }}</td>
      <th>Data Sinistro</th><td>{{ $sinistro->data_sinistro?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
      <th>N° Polizza Cliente</th><td>{{ $sinistro->numero_polizza_cliente ?? '—' }}</td>
      <th>Stato</th>
      <td><span class="badge {{ $sinistro->stato->badgeClass() }}">{{ $sinistro->stato->label() }}</span></td>
    </tr>
    @if($sinistro->luogo_sinistro)
    <tr><th>Luogo</th><td colspan="3">{{ $sinistro->luogo_sinistro }}</td></tr>
    @endif
    @if($sinistro->liquidatore_nome)
    <tr>
      <th>Liquidatore</th><td>{{ $sinistro->liquidatore_nome }}</td>
      <th>Tel./Email</th><td>{{ $sinistro->liquidatore_telefono }} {{ $sinistro->liquidatore_email }}</td>
    </tr>
    @endif
  </table>

  @if($perizia)
  <h3>Perizia</h3>
  <table>
    <tr>
      <th width="25%">Perito</th><td>{{ $perizia->perito_nome ?? '—' }}</td>
      <th width="25%">Data Ricezione</th><td>{{ $perizia->data_ricezione?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
      <th>Importo Liquidato</th><td>€ {{ number_format($perizia->importo_liquidato ?? 0, 2, ',', '.') }}</td>
      <th>Franchigia</th><td>€ {{ number_format($perizia->importo_franchigia ?? 0, 2, ',', '.') }}</td>
    </tr>
    <tr>
      <th>Scoperto %</th><td>{{ $perizia->importo_scoperto_percentuale ?? 0 }}%</td>
      <th>Importo Netto</th>
      <td class="font-bold">€ {{ number_format($perizia->importo_netto_liquidato ?? 0, 2, ',', '.') }}</td>
    </tr>
  </table>
  @endif
</div>
@endif

{{-- MAPPA DANNI SVG --}}
@if($commessa->danni->isNotEmpty() || !empty($danniPerZona))
<div class="section">
  <h2>Mappa Danni Veicolo</h2>
  <div style="display:flex;gap:16px;align-items:flex-start">
    <svg viewBox="0 0 300 450" xmlns="http://www.w3.org/2000/svg" width="180">
      @php
        $zonaClass = fn(array $d, string $z): string => ($d[$z] ?? 0) === 0 ? 'zona-ok' : (($d[$z] ?? 0) === 1 ? 'zona-danno' : 'zona-grave');
      @endphp
      <text x="150" y="11" text-anchor="middle" font-size="9" fill="#6c757d">↑ ANTERIORE</text>
      <rect x="10" y="15" width="90" height="75" rx="4" class="{{ $zonaClass($danniPerZona,'anteriore_sx') }}" stroke-width="1.5"/>
      <text x="55" y="55" text-anchor="middle" font-size="8" fill="#333">Ant. SX</text>
      @if($danniPerZona['anteriore_sx'] ?? 0)<text x="55" y="67" text-anchor="middle" font-size="9" fill="#333">({{ $danniPerZona['anteriore_sx'] }})</text>@endif
      <rect x="105" y="15" width="90" height="75" rx="4" class="{{ $zonaClass($danniPerZona,'anteriore_centro') }}" stroke-width="1.5"/>
      <text x="150" y="55" text-anchor="middle" font-size="8" fill="#333">Ant. Centro</text>
      @if($danniPerZona['anteriore_centro'] ?? 0)<text x="150" y="67" text-anchor="middle" font-size="9" fill="#333">({{ $danniPerZona['anteriore_centro'] }})</text>@endif
      <rect x="200" y="15" width="90" height="75" rx="4" class="{{ $zonaClass($danniPerZona,'anteriore_dx') }}" stroke-width="1.5"/>
      <text x="245" y="55" text-anchor="middle" font-size="8" fill="#333">Ant. DX</text>
      @if($danniPerZona['anteriore_dx'] ?? 0)<text x="245" y="67" text-anchor="middle" font-size="9" fill="#333">({{ $danniPerZona['anteriore_dx'] }})</text>@endif
      <rect x="10" y="95" width="60" height="120" rx="4" class="{{ $zonaClass($danniPerZona,'laterale_sx_ant') }}" stroke-width="1.5"/>
      <text x="40" y="160" text-anchor="middle" font-size="7" fill="#333" transform="rotate(-90 40 155)">Lat.SX Ant</text>
      <rect x="70" y="95" width="160" height="240" rx="4" class="{{ $zonaClass($danniPerZona,'tetto') }}" stroke-width="1.5"/>
      <text x="150" y="218" text-anchor="middle" font-size="9" fill="#333">TETTO</text>
      <rect x="230" y="95" width="60" height="120" rx="4" class="{{ $zonaClass($danniPerZona,'laterale_dx_ant') }}" stroke-width="1.5"/>
      <text x="260" y="160" text-anchor="middle" font-size="7" fill="#333" transform="rotate(90 260 155)">Lat.DX Ant</text>
      <rect x="10" y="220" width="60" height="115" rx="4" class="{{ $zonaClass($danniPerZona,'laterale_sx_post') }}" stroke-width="1.5"/>
      <text x="40" y="280" text-anchor="middle" font-size="7" fill="#333" transform="rotate(-90 40 278)">Lat.SX Post</text>
      <rect x="230" y="220" width="60" height="115" rx="4" class="{{ $zonaClass($danniPerZona,'laterale_dx_post') }}" stroke-width="1.5"/>
      <text x="260" y="280" text-anchor="middle" font-size="7" fill="#333" transform="rotate(90 260 278)">Lat.DX Post</text>
      <rect x="10" y="340" width="90" height="75" rx="4" class="{{ $zonaClass($danniPerZona,'posteriore_sx') }}" stroke-width="1.5"/>
      <text x="55" y="382" text-anchor="middle" font-size="8" fill="#333">Post. SX</text>
      @if($danniPerZona['posteriore_sx'] ?? 0)<text x="55" y="392" text-anchor="middle" font-size="9" fill="#333">({{ $danniPerZona['posteriore_sx'] }})</text>@endif
      <rect x="105" y="340" width="90" height="75" rx="4" class="{{ $zonaClass($danniPerZona,'posteriore_centro') }}" stroke-width="1.5"/>
      <text x="150" y="382" text-anchor="middle" font-size="8" fill="#333">Post. Centro</text>
      <rect x="200" y="340" width="90" height="75" rx="4" class="{{ $zonaClass($danniPerZona,'posteriore_dx') }}" stroke-width="1.5"/>
      <text x="245" y="382" text-anchor="middle" font-size="8" fill="#333">Post. DX</text>
      <text x="150" y="445" text-anchor="middle" font-size="9" fill="#6c757d">↓ POSTERIORE</text>
    </svg>

    {{-- Legenda --}}
    <div style="font-size:8px;margin-top:8px">
      <div style="display:flex;align-items:center;margin-bottom:4px">
        <span style="width:14px;height:14px;background:#f1f3f4;border:1px solid #dee2e6;display:inline-block;margin-right:4px"></span> Nessun danno
      </div>
      <div style="display:flex;align-items:center;margin-bottom:4px">
        <span style="width:14px;height:14px;background:#fff3cd;border:1px solid #ffc107;display:inline-block;margin-right:4px"></span> 1 danno
      </div>
      <div style="display:flex;align-items:center">
        <span style="width:14px;height:14px;background:#f8d7da;border:1px solid #dc3545;display:inline-block;margin-right:4px"></span> Danni multipli
      </div>
    </div>
  </div>
</div>
@endif

{{-- LISTA DANNI --}}
@if($commessa->danni->isNotEmpty())
<div class="section">
  <h2>Elenco Danni</h2>
  <table>
    <thead>
      <tr>
        <th>Zona</th>
        <th>Tipo</th>
        <th>Descrizione</th>
        <th class="text-right" width="8%">Q.tà</th>
        <th class="text-right" width="12%">Stima €</th>
        <th class="text-right" width="12%">Perizia €</th>
        <th class="text-center" width="10%">In perizia</th>
      </tr>
    </thead>
    <tbody>
      @foreach($commessa->danni as $danno)
      <tr>
        <td>{{ $danno->zona->label() }}</td>
        <td>{{ $danno->tipo_danno->label() }}</td>
        <td>{{ $danno->descrizione }}</td>
        <td class="text-right">{{ number_format($danno->quantita, 2, ',', '.') }}</td>
        <td class="text-right">{{ $danno->prezzo_stimato !== null ? number_format($danno->prezzo_stimato, 2, ',', '.') : '—' }}</td>
        <td class="text-right">{{ $danno->prezzo_perizia !== null ? number_format($danno->prezzo_perizia, 2, ',', '.') : '—' }}</td>
        <td class="text-center">{{ $danno->incluso_in_perizia ? '✓' : '✗' }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      @php
        $totStima = $commessa->danni->sum(fn($d) => (float)$d->prezzo_stimato * (float)$d->quantita);
        $totPerizia = $commessa->danni->where('incluso_in_perizia', true)->sum(fn($d) => (float)$d->prezzo_perizia * (float)$d->quantita);
      @endphp
      <tr class="font-bold">
        <td colspan="4" class="text-right">Totali:</td>
        <td class="text-right">€ {{ number_format($totStima, 2, ',', '.') }}</td>
        <td class="text-right">€ {{ number_format($totPerizia, 2, ',', '.') }}</td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</div>
@endif

{{-- RIEPILOGO ECONOMICO --}}
<div class="section">
  <h2>Riepilogo Economico</h2>
  <table style="max-width:50%">
    <tr><td>Totale Lavori (imponibile)</td><td class="text-right font-bold">€ {{ number_format($commessa->totale_imponibile, 2, ',', '.') }}</td></tr>
    @if($commessa->sinistro?->perizia?->importo_netto_liquidato !== null)
    <tr><td>A carico Assicurazione</td><td class="text-right">€ {{ number_format($commessa->sinistro->perizia->importo_netto_liquidato, 2, ',', '.') }}</td></tr>
    <tr class="font-bold"><td>A carico Cliente (imponibile)</td>
      <td class="text-right">€ {{ number_format($commessa->totale_imponibile - (float)$commessa->sinistro->perizia->importo_netto_liquidato, 2, ',', '.') }}</td>
    </tr>
    @endif
    <tr><td>IVA</td><td class="text-right">€ {{ number_format($commessa->totale_iva, 2, ',', '.') }}</td></tr>
    <tr class="font-bold" style="background:#f5f5f5"><td>TOTALE LORDO</td><td class="text-right">€ {{ number_format($commessa->totale_lordo, 2, ',', '.') }}</td></tr>
  </table>
</div>

{{-- FOTO DANNI (miniature) --}}
@if(!empty($fotoPerFase))
<div class="section">
  <h2>Documentazione Fotografica</h2>
  @foreach($fotoPerFase as $fase => $fotos)
  <h3>Fase: {{ ucfirst($fase) }} ({{ count($fotos) }} foto)</h3>
  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px">
    @foreach(array_slice($fotos, 0, 8) as $foto)
    @if(file_exists($foto['path']))
    <div style="text-align:center">
      <img src="{{ $foto['path'] }}" class="foto-thumb" alt="{{ $foto['nome'] }}">
      @if($foto['descrizione'])<div style="font-size:7px;max-width:80px">{{ Str::limit($foto['descrizione'], 20) }}</div>@endif
    </div>
    @endif
    @endforeach
    @if(count($fotos) > 8)<div style="font-size:8px;color:#666">... e altre {{ count($fotos) - 8 }} foto</div>@endif
  </div>
  @endforeach
</div>
@endif

{{-- FIRME --}}
<div class="row section" style="margin-top:30px">
  <div class="col text-center">
    @if($commessa->firma_cliente_svg)
    {!! $commessa->firma_cliente_svg !!}
    <div style="border-top:1px solid #333;margin-top:4px;padding-top:2px;font-size:8px">Firma Cliente — {{ $commessa->cliente->nome_completo }}</div>
    @else
    <div style="border-top:1px solid #333;margin-top:60px;padding-top:2px;font-size:8px">Firma Cliente</div>
    @endif
  </div>
  <div class="col text-center">
    <div style="border-top:1px solid #333;margin-top:60px;padding-top:2px;font-size:8px">Firma Accettatore</div>
  </div>
</div>

<div class="disclaimer">
  Documento generato da Officina Hub il {{ now()->format('d/m/Y H:i') }} — ad uso interno officina
</div>
</body>
</html>
