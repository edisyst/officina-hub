<div>

  {{-- Feedback --}}
  @if($messaggio)
  <div class="alert alert-{{ $messaggioTipo }} alert-dismissible">
    <button type="button" class="close" wire:click="$set('messaggio', '')"><span>&times;</span></button>
    {{ $messaggio }}
  </div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Scadenziario Richiami</h3>
      <button class="btn btn-primary btn-sm" wire:click="apriModal()">
        <i class="fas fa-plus"></i> Nuova Scadenza
      </button>
    </div>

    {{-- Filtri --}}
    <div class="card-body border-bottom">
      <div class="row">
        <div class="col-md-2">
          <select class="form-control form-control-sm" wire:model.live="filtraTipo">
            <option value="">Tutti i tipi</option>
            @foreach($this->tipi as $t)
              <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="filtraCliente"
            placeholder="Cerca cliente...">
        </div>
        <div class="col-md-2">
          <select class="form-control form-control-sm" wire:model.live="filtraStatoNotifica">
            <option value="">Tutte le notifiche</option>
            <option value="notificata">Notificata</option>
            <option value="non_notificata">Non notificata</option>
            <option value="disabilitata">Disabilitata</option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="date" class="form-control form-control-sm" wire:model.live="dataDa" placeholder="Da">
        </div>
        <div class="col-md-2">
          <input type="date" class="form-control form-control-sm" wire:model.live="dataA" placeholder="A">
        </div>
      </div>
    </div>

    {{-- Legenda colori --}}
    <div class="card-body py-2 border-bottom">
      <small class="text-muted mr-3">Urgenza:</small>
      <span class="badge badge-success mr-1">&gt; 60 gg</span>
      <span class="badge badge-info mr-1">15–60 gg</span>
      <span class="badge badge-warning mr-1">1–14 gg</span>
      <span class="badge badge-danger">Scaduta</span>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Veicolo</th>
              <th>Cliente</th>
              <th>Scadenza</th>
              <th>Notifica</th>
              <th class="text-right">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($this->scadenze as $s)
            @php
              $giorni = $s->giorni_alla_scadenza;
              $colore = $s->colore_urgenza;
            @endphp
            <tr class="table-{{ $colore === 'success' ? '' : $colore }}{{ $colore === 'success' ? '' : ' bg-'.($colore === 'danger' ? 'danger' : ($colore === 'warning' ? 'warning' : 'info')) }}" style="border-left: 4px solid {{ $colore === 'danger' ? '#dc3545' : ($colore === 'warning' ? '#fd7e14' : ($colore === 'info' ? '#17a2b8' : '#28a745')) }}">
              <td>
                <span class="badge badge-secondary">{{ $s->tipo->label() }}</span>
                @if($s->descrizione)
                  <br><small class="text-muted">{{ $s->descrizione }}</small>
                @endif
              </td>
              <td>
                {{ $s->veicolo?->targa ?? '—' }}
                <br><small class="text-muted">{{ $s->veicolo?->marca }} {{ $s->veicolo?->modello }}</small>
              </td>
              <td>{{ $s->cliente?->nome_completo ?? '—' }}</td>
              <td>
                <strong>{{ $s->data_scadenza->format('d/m/Y') }}</strong>
                <br>
                @if($giorni < 0)
                  <small class="text-danger">Scaduta da {{ abs($giorni) }} gg</small>
                @else
                  <small class="text-muted">tra {{ $giorni }} giorni</small>
                @endif
              </td>
              <td>
                @if($s->notifica_disabilitata)
                  <span class="badge badge-secondary">Disabilitata</span>
                @elseif($s->notifica_inviata_at)
                  <span class="badge badge-success">{{ $s->notifica_inviata_at->format('d/m/Y') }}</span>
                @else
                  <span class="badge badge-warning">In attesa</span>
                @endif
              </td>
              <td class="text-right">
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-secondary" wire:click="apriModal({{ $s->id }})" title="Modifica">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button class="btn btn-outline-{{ $s->notifica_disabilitata ? 'success' : 'warning' }}"
                    wire:click="toggleNotifica({{ $s->id }})"
                    title="{{ $s->notifica_disabilitata ? 'Abilita notifica' : 'Disabilita notifica' }}">
                    <i class="fas fa-{{ $s->notifica_disabilitata ? 'bell' : 'bell-slash' }}"></i>
                  </button>
                  <button class="btn btn-outline-primary" wire:click="inviaOra({{ $s->id }})" title="Invia richiamo ora">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                  <button class="btn btn-outline-info" wire:click="apriStorico({{ $s->id }})" title="Storico notifiche">
                    <i class="fas fa-history"></i>
                  </button>
                  <button class="btn btn-outline-danger" wire:click="elimina({{ $s->id }})"
                    wire:confirm="Eliminare questa scadenza?" title="Elimina">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>

                {{-- Storico notifiche espandibile --}}
                @if($storicoAperto === $s->id)
                <div class="mt-2 text-left">
                  <table class="table table-xs table-bordered mb-0" style="font-size:0.78rem">
                    <thead><tr><th>Data</th><th>Stato</th><th>Destinatario</th></tr></thead>
                    <tbody>
                      @forelse($s->notificheLog as $log)
                        <tr>
                          <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                          <td><span class="badge badge-{{ $log->stato === \App\Enums\StatoNotifica::Inviata ? 'success' : ($log->stato === \App\Enums\StatoNotifica::Fallita ? 'danger' : 'warning') }}">{{ $log->stato->label() }}</span></td>
                          <td>{{ $log->destinatario }}</td>
                        </tr>
                      @empty
                        <tr><td colspan="3" class="text-muted">Nessuna notifica inviata.</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-3">Nessuna scadenza trovata.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($this->scadenze->hasPages())
    <div class="card-footer">
      {{ $this->scadenze->links() }}
    </div>
    @endif
  </div>

  {{-- Modal crea/modifica scadenza --}}
  @if($modalAperto)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $scadenzaId ? 'Modifica' : 'Nuova' }} Scadenza</h5>
          <button type="button" class="close" wire:click="$set('modalAperto', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tipo *</label>
            <select class="form-control @error('tipo') is-invalid @enderror" wire:model="tipo">
              <option value="">— Seleziona —</option>
              @foreach($this->tipi as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
              @endforeach
            </select>
            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Descrizione</label>
            <input type="text" class="form-control" wire:model="descrizione">
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Data scadenza *</label>
                <input type="date" class="form-control @error('dataScadenza') is-invalid @enderror" wire:model="dataScadenza">
                @error('dataScadenza')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>KM scadenza</label>
                <input type="number" class="form-control" wire:model="kmScadenza" min="0">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Notifica N giorni prima</label>
            <input type="number" class="form-control" wire:model="notificaGiorniPrima" min="1" max="365">
          </div>
          <div class="form-group">
            <label>ID Cliente *</label>
            <input type="number" class="form-control @error('clienteId') is-invalid @enderror"
              wire:model="clienteId" placeholder="ID cliente">
            @error('clienteId')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>ID Veicolo *</label>
            <input type="number" class="form-control @error('veicoloId') is-invalid @enderror"
              wire:model="veicoloId" placeholder="ID veicolo">
            @error('veicoloId')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" wire:click="$set('modalAperto', false)">Annulla</button>
          <button class="btn btn-primary" wire:click="salva">Salva</button>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal scadenze suggerite --}}
  @if($modalScadenzeSuggerite)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-calendar-check text-success mr-2"></i>Scadenze suggerite</h5>
        </div>
        <div class="modal-body">
          <p>Dalla commessa appena consegnata sono state rilevate le seguenti scadenze. Selezionare quelle da creare:</p>
          @foreach($suggerimenti as $i => $s)
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="sug_{{ $i }}"
              wire:model="tipiSelezionati" value="{{ $s['tipo'] }}">
            <label class="custom-control-label" for="sug_{{ $i }}">
              <strong>{{ \App\Enums\TipoScadenza::from($s['tipo'])->label() }}</strong>
              — {{ $s['descrizione'] }}
              <small class="text-muted">({{ \Carbon\Carbon::parse($s['data_scadenza'])->format('d/m/Y') }})</small>
            </label>
          </div>
          @endforeach
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" wire:click="$set('modalScadenzeSuggerite', false)">Ignora</button>
          <button class="btn btn-success" wire:click="confermaSuggerimenti">
            <i class="fas fa-check"></i> Crea scadenze selezionate
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

</div>
