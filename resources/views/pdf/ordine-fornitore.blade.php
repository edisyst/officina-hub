<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Ordine {{ $ordine->numero }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
    h1 { font-size: 16px; margin-bottom: 4px; }
    .header { margin-bottom: 20px; }
    .info-block { margin-bottom: 12px; }
    .label { font-weight: bold; color: #555; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #f0f0f0; border: 1px solid #ccc; padding: 5px 6px; text-align: left; font-size: 10px; }
    td { border: 1px solid #ddd; padding: 5px 6px; vertical-align: top; }
    .text-right { text-align: right; }
    .footer { margin-top: 30px; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 8px; }
    .badge { font-size: 10px; padding: 2px 6px; border-radius: 3px; }
  </style>
</head>
<body>
  <div class="header">
    <h1>ORDINE FORNITORE</h1>
    <div style="float:left; width:50%">
      <div><span class="label">Numero ordine:</span> {{ $ordine->numero }}</div>
      <div><span class="label">Data:</span> {{ $ordine->data_ordine->format('d/m/Y') }}</div>
      @if($ordine->data_consegna_prevista)
      <div><span class="label">Consegna prevista:</span> {{ $ordine->data_consegna_prevista->format('d/m/Y') }}</div>
      @endif
      <div><span class="label">Stato:</span> {{ $ordine->stato->label() }}</div>
    </div>
    <div style="float:right; width:45%; text-align:right">
      <div class="label">Fornitore:</div>
      <div>{{ $ordine->fornitore->ragione_sociale }}</div>
      @if($ordine->fornitore->indirizzo)
        <div>{{ $ordine->fornitore->indirizzo }}</div>
      @endif
      @if($ordine->fornitore->partita_iva)
        <div>P.IVA: {{ $ordine->fornitore->partita_iva }}</div>
      @endif
    </div>
    <div style="clear:both"></div>
  </div>

  @if($ordine->note)
  <div class="info-block">
    <span class="label">Note:</span> {{ $ordine->note }}
  </div>
  @endif

  <table>
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th style="width:110px">Cod. fornitore</th>
        <th>Descrizione</th>
        <th style="width:80px" class="text-right">Q.tà</th>
        <th style="width:100px" class="text-right">Prezzo att. €</th>
      </tr>
    </thead>
    <tbody>
      @foreach($ordine->righe as $i => $riga)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $riga->codice_fornitore ?? '—' }}</td>
        <td>{{ $riga->descrizione }}</td>
        <td class="text-right">{{ $riga->quantita_ordinata }}</td>
        <td class="text-right">
          @if($riga->prezzo_unitario_atteso)
            € {{ number_format((float)$riga->prezzo_unitario_atteso, 2, ',', '.') }}
          @else
            —
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="footer">
    <p>Documento generato il {{ now()->format('d/m/Y H:i') }} &mdash; da confermare con ordine ufficiale.</p>
  </div>
</body>
</html>
