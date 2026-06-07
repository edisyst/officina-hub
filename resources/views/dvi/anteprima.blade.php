<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Anteprima DVI — {{ $ispezione->commessa->veicolo?->targa }}</title>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:16px;background:#f5f5f5;color:#222;line-height:1.5}
    .preview-banner{background:#f59e0b;color:#fff;padding:.75rem 1rem;font-weight:600;text-align:center;font-size:.9rem}
    .header{background:#1a1a2e;color:#fff;padding:1rem}
    .header h1{font-size:1.1rem;font-weight:600}
    .header small{font-size:.85rem;opacity:.8;display:block}
    .container{max-width:600px;margin:0 auto;padding:1rem}
    .intro{background:#fff;border-radius:8px;padding:1rem;margin-bottom:1rem;border-left:4px solid #1a1a2e}
    .card-voce{background:#fff;border-radius:8px;margin-bottom:1rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)}
    .card-voce-header{padding:.75rem 1rem;display:flex;align-items:flex-start;gap:.75rem}
    .badge-urgenza{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .7rem;border-radius:20px;font-size:.8rem;font-weight:600;white-space:nowrap}
    .badge-ok{background:#d1fae5;color:#065f46}
    .badge-attenzione{background:#fef3c7;color:#92400e}
    .badge-urgente{background:#fee2e2;color:#991b1b}
    .voce-title{font-weight:600;font-size:1rem}
    .voce-cat{font-size:.8rem;color:#666;text-transform:uppercase;letter-spacing:.05em}
    .voce-note{font-size:.85rem;color:#555;padding:0 1rem .5rem}
    .prezzo{font-size:.9rem;font-weight:600;color:#059669;padding:0 1rem .5rem}
    .gallery{display:flex;overflow-x:auto;gap:.5rem;padding:.5rem 1rem;-webkit-overflow-scrolling:touch}
    .gallery img,.gallery video{height:140px;min-width:140px;max-width:200px;object-fit:cover;border-radius:6px;flex-shrink:0}
    .btn-placeholder{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;height:56px;border-radius:8px;font-size:1rem;font-weight:600;margin:.25rem 0;opacity:.5;cursor:default}
    .btn-approvo{background:#10b981;color:#fff;border:none}
    .btn-rimando{background:#9ca3af;color:#fff;border:none}
    .azioni{padding:.75rem 1rem 1rem}
    .back-link{display:inline-block;margin-bottom:1rem;color:#1a1a2e;text-decoration:none;font-size:.9rem}
    .back-link:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="preview-banner">
    ⚠️ ANTEPRIMA — Questa visualizzazione non è visibile al cliente fino all'invio
  </div>

  <div class="header">
    <h1>{{ setting('officina_nome') }}</h1>
    <small>{{ $ispezione->commessa->veicolo?->targa }} — {{ $ispezione->commessa->cliente->nome_completo }}</small>
  </div>

  <div class="container">
    <a href="{{ route('commesse.show', $ispezione->commessa_id) }}" class="back-link">← Torna alla commessa</a>

    <div class="intro">
      <p style="font-weight:600;margin-bottom:.25rem">Ispezione del tuo veicolo</p>
      <p style="font-size:.9rem;color:#555">Il nostro tecnico ha esaminato il tuo veicolo e trovato i seguenti punti da segnalarti.</p>
    </div>

    @if($ispezione->note_meccanico)
    <div style="background:#fff;border-radius:8px;padding:1rem;margin-bottom:1rem;border-left:3px solid #6b7280">
      <p style="font-size:.9rem;color:#555">{{ $ispezione->note_meccanico }}</p>
    </div>
    @endif

    @foreach($ispezione->voci as $voce)
    <div class="card-voce">
      <div class="card-voce-header">
        <span class="badge-urgenza badge-{{ $voce->urgenza->value }}">
          {{ $voce->urgenza->label() }}
        </span>
        <div>
          <div class="voce-cat">{{ $voce->categoria }}</div>
          <div class="voce-title">{{ $voce->descrizione }}</div>
        </div>
      </div>

      @if($voce->note)
      <p class="voce-note">{{ $voce->note }}</p>
      @endif

      @if($voce->prezzo_stimato)
      <p class="prezzo">Costo stimato: € {{ number_format($voce->prezzo_stimato, 2, ',', '.') }}</p>
      @endif

      @if($voce->media->count())
      <div class="gallery">
        @foreach($voce->media as $media)
          @if($media->tipo->value === 'foto')
          <img src="{{ route('dvi.media', $media->id) }}" alt="foto" loading="lazy">
          @else
          <video src="{{ route('dvi.media', $media->id) }}" controls playsinline preload="metadata"></video>
          @endif
        @endforeach
      </div>
      @endif

      <div class="azioni">
        <button class="btn-placeholder btn-approvo" disabled>✓ Approvo — fate pure</button>
        <button class="btn-placeholder btn-rimando" disabled>🕐 Rimando a prossima visita</button>
      </div>
    </div>
    @endforeach

    <p style="text-align:center;color:#888;font-size:.85rem;margin-top:1rem">
      {{ setting('officina_nome') }} — {{ setting('officina_telefono') }}
    </p>
  </div>
</body>
</html>
