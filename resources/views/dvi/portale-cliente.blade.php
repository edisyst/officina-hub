<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Ispezione veicolo — {{ setting('officina_nome') }}</title>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:16px;background:#f5f5f5;color:#222;line-height:1.5}
    .header{background:#1a1a2e;color:#fff;padding:1rem;display:flex;align-items:center;gap:1rem}
    .header img{height:48px;width:48px;object-fit:contain;border-radius:6px}
    .header h1{font-size:1.1rem;font-weight:600;margin:0}
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
    .gallery{display:flex;overflow-x:auto;gap:.5rem;padding:.5rem 1rem;-webkit-overflow-scrolling:touch;scrollbar-width:none}
    .gallery::-webkit-scrollbar{display:none}
    .gallery img,.gallery video{height:140px;min-width:140px;max-width:200px;object-fit:cover;border-radius:6px;flex-shrink:0}
    .gallery video{background:#000}
    .azioni{display:flex;flex-direction:column;gap:.5rem;padding:.75rem 1rem 1rem}
    .btn-approvo{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;height:56px;border:2px solid #10b981;background:#10b981;color:#fff;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;transition:.15s}
    .btn-approvo:hover{background:#059669}
    .btn-rimando{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;height:56px;border:2px solid #9ca3af;background:#f9fafb;color:#374151;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;transition:.15s}
    .btn-rimando:hover{background:#e5e7eb}
    .btn-selected-approvo{background:#059669;border-color:#059669;color:#fff}
    .btn-selected-rimando{background:#6b7280;border-color:#6b7280;color:#fff}
    .badge-risposta{display:inline-flex;align-items:center;gap:.3rem;padding:.4rem .9rem;border-radius:20px;font-size:.85rem;font-weight:600;margin:.75rem 1rem}
    .badge-approvato{background:#d1fae5;color:#065f46}
    .badge-rimandato{background:#f3f4f6;color:#374151}
    .note-cliente{padding:1rem;background:#fff;border-radius:8px;margin-bottom:1rem}
    .note-cliente label{font-weight:600;display:block;margin-bottom:.5rem}
    .note-cliente textarea{width:100%;border:1px solid #d1d5db;border-radius:6px;padding:.5rem;font-size:.95rem;resize:vertical;min-height:80px}
    .btn-conferma{display:block;width:100%;height:60px;background:#1a1a2e;color:#fff;border:none;border-radius:8px;font-size:1.1rem;font-weight:600;cursor:pointer;transition:.15s;margin-bottom:2rem}
    .btn-conferma:hover{background:#0d0d1f}
    .btn-conferma:disabled{opacity:.4;cursor:not-allowed}
    .errore{max-width:480px;margin:2rem auto;text-align:center;padding:2rem;background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
    .errore h2{margin-bottom:.75rem;color:#1a1a2e}
    .scadenza-banner{background:#fef3c7;color:#92400e;padding:.5rem 1rem;font-size:.85rem;text-align:center}
  </style>
</head>
<body>

@if(isset($errore))
  <div class="header">
    <div>
      <h1>{{ setting('officina_nome') }}</h1>
    </div>
  </div>
  <div class="errore">
    @if($errore === 'scaduto')
      <h2>Link scaduto</h2>
      <p>Il link per la visualizzazione del report è scaduto il {{ $ispezione->link_scade_at->format('d/m/Y') }}.</p>
      <p>Contatta l'officina per ricevere un nuovo link.</p>
    @elseif($errore === 'non_trovato')
      <h2>Pagina non trovata</h2>
      <p>Il link non è valido o è già stato utilizzato.</p>
    @endif
    <p style="margin-top:1.5rem;color:#666;font-size:.9rem">{{ setting('officina_nome') }} — {{ setting('officina_telefono') }}</p>
  </div>
@elseif($giaRisposto)
  {{-- Risposta già inviata --}}
  <div class="header">
    <div>
      <h1>{{ setting('officina_nome') }}</h1>
      <small>{{ $ispezione->commessa->veicolo?->targa }}</small>
    </div>
  </div>
  <div class="container">
    <div class="intro" style="border-left-color:#10b981">
      <p style="font-weight:600;color:#065f46;margin-bottom:.25rem"><i>✓</i> Risposta già inviata</p>
      <p style="font-size:.9rem;color:#555">Hai già risposto a questa ispezione il {{ $ispezione->approvata_at?->format('d/m/Y') }}. Grazie per la tua collaborazione.</p>
    </div>
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
      @if($voce->stato_approvazione)
      <div class="badge-risposta {{ $voce->stato_approvazione->value === 'approvato' ? 'badge-approvato' : 'badge-rimandato' }}">
        {{ $voce->stato_approvazione->value === 'approvato' ? '✓ Approvato — fate pure' : '→ Rimandato a prossima visita' }}
      </div>
      @endif
    </div>
    @endforeach
    <p style="text-align:center;color:#888;font-size:.85rem;margin-top:1rem">{{ setting('officina_nome') }} — {{ setting('officina_telefono') }}</p>
  </div>
@else
  {{-- Portale attivo --}}
  @php $scadeGiorni = now()->diffInDays($ispezione->link_scade_at, false); @endphp
  @if($scadeGiorni <= 2)
  <div class="scadenza-banner">⚠️ Questo link scade il {{ $ispezione->link_scade_at->format('d/m/Y') }}</div>
  @endif

  <div class="header">
    <div>
      <h1>{{ setting('officina_nome') }}</h1>
      <small>{{ $ispezione->commessa->veicolo?->targa }} — {{ $ispezione->commessa->cliente->nome_completo }}</small>
    </div>
  </div>

  <form id="form-dvi" method="POST" action="{{ route('dvi.salva-risposte', $token) }}">
    @csrf
    <div class="container">
      <div class="intro">
        <p style="font-weight:600;margin-bottom:.25rem">Ispezione del tuo veicolo</p>
        <p style="font-size:.9rem;color:#555">Il nostro tecnico ha esaminato il tuo veicolo e trovato i seguenti punti da segnalarti. Indica per ciascuno cosa vuoi fare.</p>
      </div>

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
            <img src="{{ route('dvi.media.cliente', [$token, $media->id]) }}"
              alt="foto" loading="lazy"
              onclick="this.requestFullscreen ? this.requestFullscreen() : null">
            @else
            <video src="{{ route('dvi.media.cliente', [$token, $media->id]) }}"
              controls playsinline preload="metadata"></video>
            @endif
          @endforeach
        </div>
        @endif

        <div class="azioni">
          <button type="button"
            id="btn-approvo-{{ $voce->id }}"
            class="btn-approvo"
            onclick="seleziona({{ $voce->id }}, 'approvato')">
            ✓ Approvo — fate pure
          </button>
          <button type="button"
            id="btn-rimando-{{ $voce->id }}"
            class="btn-rimando"
            onclick="seleziona({{ $voce->id }}, 'rimandato')">
            🕐 Rimando a prossima visita
          </button>
          <input type="hidden" name="risposte[{{ $voce->id }}]" id="input-{{ $voce->id }}" value="">
        </div>
      </div>
      @endforeach

      <div class="note-cliente">
        <label for="note-cliente">Note per l'officina (facoltative)</label>
        <textarea name="note_cliente" id="note-cliente" placeholder="Eventuali domande o richieste..."></textarea>
      </div>

      <button type="submit" id="btn-submit" class="btn-conferma" disabled>
        Conferma le mie scelte
      </button>
    </div>
  </form>

  <script>
    const totaleVoci = {{ $ispezione->voci->count() }};
    const risposte = {};

    function seleziona(voceId, scelta) {
      risposte[voceId] = scelta;
      document.getElementById('input-' + voceId).value = scelta;

      const btnA = document.getElementById('btn-approvo-' + voceId);
      const btnR = document.getElementById('btn-rimando-' + voceId);

      btnA.className = scelta === 'approvato' ? 'btn-approvo btn-selected-approvo' : 'btn-approvo';
      btnR.className = scelta === 'rimandato' ? 'btn-rimando btn-selected-rimando' : 'btn-rimando';

      const nRisposte = Object.keys(risposte).length;
      document.getElementById('btn-submit').disabled = nRisposte === 0;
    }
  </script>
@endif
</body>
</html>
