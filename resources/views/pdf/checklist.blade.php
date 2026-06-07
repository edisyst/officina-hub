<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <style>
    @font-face { font-family: 'DejaVu Sans'; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; }
    .header { padding: 12px 16px; border-bottom: 2px solid #007bff; margin-bottom: 16px; }
    .header h1 { font-size: 14pt; color: #007bff; margin-bottom: 4px; }
    .header .sub { font-size: 9pt; color: #666; }
    .info-grid { display: table; width: 100%; margin-bottom: 14px; }
    .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
    .info-row { margin-bottom: 4px; font-size: 9pt; }
    .info-row strong { display: inline-block; min-width: 90px; color: #555; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; }
    .badge-success { background: #28a745; color: #fff; }
    .badge-secondary { background: #6c757d; color: #fff; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #007bff; color: #fff; padding: 5px 8px; font-size: 9pt; text-align: left; }
    td { border: 1px solid #dee2e6; padding: 5px 8px; font-size: 9pt; vertical-align: top; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .resp-si { color: #28a745; font-weight: bold; }
    .resp-no { color: #dc3545; font-weight: bold; }
    .footer { margin-top: 20px; border-top: 1px solid #dee2e6; padding-top: 8px; font-size: 8pt; color: #999; text-align: center; }
  </style>
</head>
<body>

<div class="header">
  <h1><i>Checklist:</i> {{ $compilata->template->nome }}</h1>
  <div class="sub">Generato il {{ now()->format('d/m/Y H:i') }}</div>
</div>

<div class="info-grid">
  <div class="info-col">
    <div class="info-row"><strong>Commessa:</strong> {{ $compilata->commessa->numero }}</div>
    <div class="info-row"><strong>Cliente:</strong>
      {{ $compilata->commessa->cliente->nome ?? '' }} {{ $compilata->commessa->cliente->cognome ?? '' }}
      {{ $compilata->commessa->cliente->ragione_sociale ?? '' }}
    </div>
    <div class="info-row"><strong>Veicolo:</strong>
      {{ $compilata->commessa->veicolo->marca ?? '' }} {{ $compilata->commessa->veicolo->modello ?? '' }}
      @if($compilata->commessa->veicolo->targa) ({{ $compilata->commessa->veicolo->targa }}) @endif
    </div>
  </div>
  <div class="info-col">
    <div class="info-row"><strong>Meccanico:</strong> {{ $compilata->meccanico->name }}</div>
    <div class="info-row"><strong>Avviata:</strong> {{ $compilata->created_at->format('d/m/Y H:i') }}</div>
    <div class="info-row"><strong>Completata:</strong>
      @if($compilata->completata_at)
        <span class="badge badge-success">{{ $compilata->completata_at->format('d/m/Y H:i') }}</span>
      @else
        <span class="badge badge-secondary">Non completata</span>
      @endif
    </div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th style="width:5%">#</th>
      <th style="width:40%">Voce</th>
      <th style="width:15%">Tipo</th>
      <th>Risposta</th>
    </tr>
  </thead>
  <tbody>
    @foreach($compilata->template->voci as $i => $voce)
      @php $risposta = $compilata->risposte->firstWhere('checklist_voce_id', $voce->id) @endphp
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>
          {{ $voce->etichetta }}
          @if($voce->obbligatoria) <span class="badge badge-secondary">obblig.</span> @endif
        </td>
        <td>{{ $voce->tipo }}</td>
        <td>
          @if($risposta)
            @if($voce->tipo === 'si_no')
              <span class="{{ $risposta->valore_booleano ? 'resp-si' : 'resp-no' }}">
                {{ $risposta->valore_booleano ? '✓ Sì' : '✗ No' }}
              </span>
            @elseif($voce->tipo === 'numerico')
              {{ $risposta->valore_numerico }} {{ $voce->unita_misura }}
            @elseif($voce->tipo === 'testo_libero')
              {{ $risposta->valore_testo }}
            @elseif($voce->tipo === 'foto_obbligatoria')
              @if($risposta->foto_path)
                <span class="badge badge-success">Foto acquisita</span>
              @else
                <span class="badge badge-secondary">—</span>
              @endif
            @endif
          @else
            <span style="color:#ccc">—</span>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

<div class="footer">
  Officina Hub — Documento generato automaticamente il {{ now()->format('d/m/Y \a\l\l\e H:i') }}
</div>

</body>
</html>
