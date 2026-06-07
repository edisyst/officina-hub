<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Grazie — {{ setting('officina_nome') }}</title>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:16px;background:#f5f5f5;color:#222;line-height:1.5}
    .header{background:#1a1a2e;color:#fff;padding:1rem}
    .header h1{font-size:1.1rem;font-weight:600}
    .container{max-width:520px;margin:0 auto;padding:1.5rem 1rem}
    .success-card{background:#fff;border-radius:12px;padding:2rem;text-align:center;margin-bottom:1.5rem;box-shadow:0 2px 12px rgba(0,0,0,.08)}
    .check-icon{width:64px;height:64px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem}
    .summary-card{background:#fff;border-radius:8px;padding:1rem;margin-bottom:1rem}
    .summary-card h3{font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:#888;margin-bottom:.75rem}
    .voce-row{display:flex;align-items:center;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f0f0f0}
    .voce-row:last-child{border-bottom:none}
    .badge-sm{padding:.2rem .6rem;border-radius:12px;font-size:.75rem;font-weight:600}
    .badge-approvato{background:#d1fae5;color:#065f46}
    .badge-rimandato{background:#f3f4f6;color:#374151}
    .footer{text-align:center;color:#888;font-size:.85rem;margin-top:2rem}
  </style>
</head>
<body>
  <div class="header">
    <h1>{{ setting('officina_nome') }}</h1>
  </div>
  <div class="container">
    <div class="success-card">
      <div class="check-icon">✓</div>
      <h2 style="font-size:1.3rem;margin-bottom:.5rem">Risposta inviata!</h2>
      <p style="color:#555;font-size:.95rem">Grazie, {{ $ispezione->commessa->cliente->nome_completo }}.<br>Abbiamo ricevuto le tue scelte per il veicolo <strong>{{ $ispezione->commessa->veicolo?->targa }}</strong>.</p>
    </div>

    @php
      $approvate = $ispezione->voci->filter(fn($v) => $v->stato_approvazione?->value === 'approvato');
      $rimandate = $ispezione->voci->filter(fn($v) => $v->stato_approvazione?->value === 'rimandato');
      $importo   = $approvate->sum('prezzo_stimato');
    @endphp

    @if($approvate->count())
    <div class="summary-card">
      <h3>Interventi approvati ({{ $approvate->count() }})</h3>
      @foreach($approvate as $v)
      <div class="voce-row">
        <span>{{ $v->descrizione }}</span>
        <span class="badge-sm badge-approvato">✓ Approvato</span>
      </div>
      @endforeach
      @if($importo > 0)
      <div style="margin-top:.75rem;text-align:right;font-weight:600;color:#059669">
        Totale stimato: € {{ number_format($importo, 2, ',', '.') }}
      </div>
      @endif
    </div>
    @endif

    @if($rimandate->count())
    <div class="summary-card">
      <h3>Rimandati a prossima visita ({{ $rimandate->count() }})</h3>
      @foreach($rimandate as $v)
      <div class="voce-row">
        <span>{{ $v->descrizione }}</span>
        <span class="badge-sm badge-rimandato">→ Rimandato</span>
      </div>
      @endforeach
    </div>
    @endif

    <div class="footer">
      <p>Ti contatteremo presto per organizzare i lavori.</p>
      <p style="margin-top:.5rem">{{ setting('officina_nome') }}<br>{{ setting('officina_telefono') }}</p>
    </div>
  </div>
</body>
</html>
