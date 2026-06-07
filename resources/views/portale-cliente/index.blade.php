<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Area Cliente — {{ setting('officina_nome', 'Officina Hub') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <style>
    body { background: #f8f9fa; }
    .header { background: #343a40; color: #fff; padding: 1.5rem; margin-bottom: 2rem; }
    .badge-bozza { background:#6c757d; }
    .badge-accettata { background:#17a2b8; }
    .badge-in_lavorazione { background:#007bff; }
    .badge-sospesa { background:#ffc107; color:#212529; }
    .badge-completata { background:#28a745; }
    .badge-consegnata { background:#20c997; }
    .badge-fatturata { background:#6f42c1; }
    .urgenza-danger { border-left: 4px solid #dc3545; }
    .urgenza-warning { border-left: 4px solid #fd7e14; }
    .urgenza-info { border-left: 4px solid #17a2b8; }
    .urgency-success { border-left: 4px solid #28a745; }
  </style>
</head>
<body>

<div class="header">
  @if(file_exists(public_path('images/logo.png')))
    <img src="{{ asset('images/logo.png') }}" alt="" style="max-height:40px; margin-right:12px;">
  @endif
  <strong>{{ setting('officina_nome', 'Officina Hub') }}</strong>
  <span class="ml-3 text-light small">Area Cliente</span>
</div>

<div class="container pb-5">

  <div class="row mb-3">
    <div class="col">
      <h5>Benvenuto/a, <strong>{{ $cliente->nome_completo }}</strong></h5>
      <small class="text-muted">Dati aggiornati al {{ now()->format('d/m/Y H:i') }}</small>
    </div>
  </div>

  {{-- Ultime commesse --}}
  <div class="card mb-4">
    <div class="card-header"><strong>Ultime commesse</strong></div>
    <div class="card-body p-0">
      @forelse($commesse as $commessa)
      <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong>{{ $commessa->numero }}</strong>
            <span class="ml-2 badge badge-{{ $commessa->stato->value }}">{{ $commessa->stato->label() }}</span>
          </div>
          <small class="text-muted">{{ $commessa->data_ingresso?->format('d/m/Y') }}</small>
        </div>
        @if($commessa->veicolo)
        <div class="small text-muted mt-1">
          {{ $commessa->veicolo->targa }} — {{ $commessa->veicolo->marca }} {{ $commessa->veicolo->modello }}
        </div>
        @endif
        @if($commessa->descrizione_cliente)
        <div class="small mt-1">{{ $commessa->descrizione_cliente }}</div>
        @endif
      </div>
      @empty
      <div class="p-3 text-muted">Nessuna commessa trovata.</div>
      @endforelse
    </div>
  </div>

  {{-- Scadenze imminenti --}}
  @if($scadenze->count() > 0)
  <div class="card">
    <div class="card-header"><strong>Scadenze imminenti</strong></div>
    <div class="card-body p-0">
      @foreach($scadenze as $scadenza)
      @php
        $giorni = $scadenza->giorni_alla_scadenza;
        $classe = $giorni < 0 ? 'danger' : ($giorni <= 14 ? 'warning' : ($giorni <= 60 ? 'info' : 'success'));
      @endphp
      <div class="p-3 urgenza-{{ $classe }} {{ !$loop->last ? 'border-bottom' : '' }}">
        <div class="d-flex justify-content-between">
          <div>
            <strong>{{ $scadenza->tipo->label() }}</strong>
            @if($scadenza->descrizione)
              <span class="text-muted small ml-1">— {{ $scadenza->descrizione }}</span>
            @endif
          </div>
          <small>{{ $scadenza->data_scadenza->format('d/m/Y') }}</small>
        </div>
        <div class="small text-muted mt-1">
          {{ $scadenza->veicolo->targa ?? '' }}
          @if($giorni < 0)
            <span class="text-danger font-weight-bold">Scaduta da {{ abs($giorni) }} giorni</span>
          @else
            — scade tra {{ $giorni }} giorni
          @endif
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="mt-4 text-center small text-muted">
    Per fissare un appuntamento contattaci:
    @if(setting('officina_telefono')) <strong>{{ setting('officina_telefono') }}</strong> @endif
    @if(setting('officina_email')) — <a href="mailto:{{ setting('officina_email') }}">{{ setting('officina_email') }}</a> @endif
  </div>

</div>
</body>
</html>
