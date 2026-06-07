<div>
  @auth
  @php $user = auth()->user(); @endphp

  <!-- Admin / Accettatore -->
  @if($user->hasAnyRole(['admin', 'accettatore']))
  <div class="row">
    <!-- Commesse in lavorazione -->
    <div class="col-md-3 col-sm-6">
      <div class="info-box bg-primary">
        <span class="info-box-icon"><i class="fas fa-wrench"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">In Lavorazione</span>
          <span class="info-box-number">{{ $dati['commesse_in_lavorazione'] ?? 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Appuntamenti oggi -->
    <div class="col-md-3 col-sm-6">
      <div class="info-box bg-info">
        <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Appuntamenti Oggi</span>
          <span class="info-box-number">{{ $dati['appuntamenti_oggi']?->count() ?? 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Attesa consegna -->
    <div class="col-md-3 col-sm-6">
      <div class="info-box {{ ($dati['commesse_attesa_consegna'] ?? 0) > 3 ? 'bg-danger' : 'bg-success' }}">
        <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Da Consegnare</span>
          <span class="info-box-number">{{ $dati['commesse_attesa_consegna'] ?? 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Link marcatempo (admin) -->
    @role('admin')
    <div class="col-md-3 col-sm-6">
      <div class="info-box bg-warning">
        <span class="info-box-icon"><i class="fas fa-stopwatch"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Marcatempo</span>
          <a href="{{ route('marcatempo') }}" class="btn btn-sm btn-light mt-1">Vai</a>
        </div>
      </div>
    </div>
    @endrole
  </div>

  <div class="row">
    <!-- Stato ponti -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-car-side mr-2"></i>Stato Ponti</h3>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse($dati['ponti'] ?? [] as $ponte)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              {{ $ponte->nome }}
              <span class="badge {{ $ponte->occupato ? 'badge-danger' : 'badge-success' }}">
                {{ $ponte->occupato ? 'Occupato' : 'Libero' }}
              </span>
            </li>
            @empty
            <li class="list-group-item text-muted">Nessun ponte configurato.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    <!-- Appuntamenti oggi -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Appuntamenti di Oggi</h3>
          <div class="card-tools">
            <a href="{{ route('agenda') }}" class="btn btn-xs btn-outline-primary">Apri Agenda</a>
          </div>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse($dati['appuntamenti_oggi'] ?? [] as $app)
            <li class="list-group-item py-2">
              <div class="d-flex justify-content-between">
                <span>
                  <span class="badge {{ $app->stato->badgeClass() }} mr-1">{{ $app->data_ora_inizio->format('H:i') }}</span>
                  {{ $app->titolo }}
                </span>
                <small class="text-muted">{{ $app->cliente?->nome_completo }}</small>
              </div>
            </li>
            @empty
            <li class="list-group-item text-muted">Nessun appuntamento oggi.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Prossimi appuntamenti 24h -->
  @if(($dati['prossimi_appuntamenti'] ?? collect())->count())
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Prossime 24 ore</h3>
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        @foreach($dati['prossimi_appuntamenti'] as $app)
        <li class="list-group-item py-2">
          <span class="badge badge-info mr-2">{{ $app->data_ora_inizio->format('d/m H:i') }}</span>
          {{ $app->titolo }}
          @if($app->veicolo?->targa) — {{ $app->veicolo->targa }} @endif
        </li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <!-- Widget DVI in attesa di risposta -->
  @if(($dati['dvi_in_attesa'] ?? collect())->count())
  <div class="card card-outline card-warning">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-camera-retro mr-2"></i>DVI in attesa di risposta
        <span class="badge badge-warning ml-2">{{ $dati['dvi_in_attesa']->count() }}</span>
      </h3>
      @if($dati['dvi_risposta_oggi'] ?? 0)
      <div class="card-tools">
        <span class="badge badge-success">{{ $dati['dvi_risposta_oggi'] }} risposta{{ $dati['dvi_risposta_oggi'] > 1 ? 'i' : '' }} oggi</span>
      </div>
      @endif
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        @foreach($dati['dvi_in_attesa'] as $dvi)
        <li class="list-group-item py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="font-weight-bold">{{ $dvi->commessa->veicolo?->targa ?? '—' }}</span>
              <span class="text-muted ml-2">{{ $dvi->commessa->cliente->nome_completo }}</span>
            </div>
            <div class="d-flex align-items-center" style="gap:.5rem">
              <small class="text-muted">inviata {{ $dvi->inviata_at?->diffForHumans() }}</small>
              <a href="{{ route('commesse.show', $dvi->commessa_id) }}" class="btn btn-xs btn-outline-primary">
                <i class="fas fa-arrow-right"></i>
              </a>
            </div>
          </div>
        </li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <!-- Widget sotto scorta -->
  @if(($dati['count_sotto_scorta'] ?? 0) > 0)
  <div class="card card-warning">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Articoli sotto scorta
        <span class="badge badge-danger ml-2">{{ $dati['count_sotto_scorta'] }}</span>
      </h3>
      <div class="card-tools">
        <a href="{{ route('magazzino.articoli') }}?sottoScorta=1" class="btn btn-xs btn-outline-light">
          Vedi tutti
        </a>
      </div>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Articolo</th><th class="text-center">Giacenza</th><th class="text-center">Scorta min.</th><th>Fornitore</th><th></th></tr></thead>
        <tbody>
          @foreach($dati['articoli_sotto_scorta'] as $art)
          <tr>
            <td>{{ $art->descrizione }}</td>
            <td class="text-center text-danger font-weight-bold">{{ $art->giacenza_attuale }} {{ $art->unita_misura->label() }}</td>
            <td class="text-center text-muted">{{ $art->scorta_minima }}</td>
            <td><small>{{ $art->fornitore?->ragione_sociale ?? '—' }}</small></td>
            <td>
              <a href="{{ route('magazzino.articoli.show', $art->id) }}" class="btn btn-xs btn-outline-secondary">
                <i class="fas fa-arrow-right"></i>
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif
  @endif

  <!-- Vista meccanico -->
  @role('meccanico')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title"><i class="fas fa-wrench mr-2"></i>Le Mie Commesse</h3>
      <a href="{{ route('marcatempo') }}" class="btn btn-sm btn-warning">
        <i class="fas fa-stopwatch mr-1"></i> Vai al Marcatempo
      </a>
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        @forelse($dati['mie_commesse'] ?? [] as $commessa)
        <li class="list-group-item">
          <a href="{{ route('commesse.show', $commessa->id) }}" class="font-weight-bold">{{ $commessa->numero }}</a>
          — {{ $commessa->veicolo->targa ?? '—' }}
          {{ $commessa->cliente->nome_completo }}
        </li>
        @empty
        <li class="list-group-item text-muted">Nessuna commessa in lavorazione.</li>
        @endforelse
      </ul>
    </div>
  </div>
  @endrole

  <!-- Vista cassa -->
  @role('cassa')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-cash-register mr-2"></i>Commesse da Fatturare</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Numero</th><th>Cliente</th><th>Veicolo</th><th class="text-right">Importo prev.</th></tr></thead>
        <tbody>
          @forelse($dati['commesse_da_fatturare'] ?? [] as $commessa)
          <tr>
            <td><a href="{{ route('commesse.show', $commessa->id) }}">{{ $commessa->numero }}</a></td>
            <td>{{ $commessa->cliente->nome_completo }}</td>
            <td>{{ $commessa->veicolo->targa ?? '—' }}</td>
            <td class="text-right">€ {{ number_format($commessa->totale_lordo, 2, ',', '.') }}</td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-muted text-center">Nessuna commessa completata da fatturare.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endrole

  @endauth
</div>
