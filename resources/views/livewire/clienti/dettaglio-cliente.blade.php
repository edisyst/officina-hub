<div>
  <div class="row">
    <div class="col-md-6">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h3 class="card-title">{{ $cliente->nome_completo }}</h3>
          <div class="card-tools">
            <span class="badge badge-info">{{ $cliente->tipo->label() }}</span>
          </div>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            @if($cliente->codice_fiscale)
            <dt class="col-5">Codice Fiscale</dt><dd class="col-7">{{ $cliente->codice_fiscale }}</dd>
            @endif
            @if($cliente->partita_iva)
            <dt class="col-5">Partita IVA</dt><dd class="col-7">{{ $cliente->partita_iva }}</dd>
            @endif
            @if($cliente->telefono)
            <dt class="col-5">Telefono</dt><dd class="col-7">{{ $cliente->telefono }}</dd>
            @endif
            @if($cliente->email)
            <dt class="col-5">Email</dt><dd class="col-7">{{ $cliente->email }}</dd>
            @endif
            @if($cliente->indirizzo)
            <dt class="col-5">Indirizzo</dt>
            <dd class="col-7">{{ $cliente->indirizzo }}<br>{{ $cliente->cap }} {{ $cliente->citta }} ({{ $cliente->provincia }})</dd>
            @endif
            @if($cliente->note)
            <dt class="col-5">Note</dt><dd class="col-7">{{ $cliente->note }}</dd>
            @endif
          </dl>
        </div>
        <div class="card-footer">
          <a href="{{ route('clienti.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Torna alla lista
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Veicoli Associati</h3>
          <div class="card-tools">
            @can('update', $cliente)
            <button wire:click="apriAssociaModal()" class="btn btn-sm btn-success">
              <i class="fas fa-link"></i> Associa Veicolo
            </button>
            @endcan
          </div>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Veicolo</th><th>Targa</th><th>Proprietario</th><th>Dal</th><th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($veicoli as $v)
              <tr>
                <td>
                  <a href="{{ route('veicoli.show', $v->id) }}">{{ $v->marca }} {{ $v->modello }}</a>
                </td>
                <td>{{ $v->targa ?? '-' }}</td>
                <td>
                  @if($v->pivot->proprietario_attuale)
                  <span class="badge badge-success">Attuale</span>
                  @else
                  <span class="badge badge-secondary">Storico</span>
                  @endif
                </td>
                <td>{{ $v->pivot->data_inizio ? \Carbon\Carbon::parse($v->pivot->data_inizio)->format('d/m/Y') : '-' }}</td>
                <td>
                  @can('update', $cliente)
                  <button wire:click="dissocia({{ $v->id }})"
                    wire:confirm="Dissociare questo veicolo dal cliente?"
                    class="btn btn-xs btn-warning">
                    <i class="fas fa-unlink"></i>
                  </button>
                  @endcan
                </td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-muted">Nessun veicolo associato.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Commesse del cliente -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Commesse</h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead>
          <tr><th>Numero</th><th>Veicolo</th><th>Tipo</th><th>Stato</th><th>Ingresso</th></tr>
        </thead>
        <tbody>
          @forelse($cliente->commesse()->with('veicolo')->latest('data_ingresso')->get() as $c)
          <tr>
            <td><a href="{{ route('commesse.show', $c->id) }}">{{ $c->numero }}</a></td>
            <td>{{ $c->veicolo->targa ?? '-' }} — {{ $c->veicolo->marca }} {{ $c->veicolo->modello }}</td>
            <td>{{ $c->tipo->label() }}</td>
            <td><span class="badge {{ $c->stato->badgeClass() }}">{{ $c->stato->label() }}</span></td>
            <td>{{ $c->data_ingresso->format('d/m/Y') }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted">Nessuna commessa.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab CRM -->
  <div class="card" id="tab-crm">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-heart mr-1 text-danger"></i> CRM
        @if ($cliente->segmento_crm)
          <span class="badge {{ $cliente->segmento_crm->badgeClass() }} ml-2">{{ $cliente->segmento_crm->label() }}</span>
        @endif
      </h3>
      <div class="card-tools">
        @role('admin')
        <div class="custom-control custom-switch">
          <input type="checkbox" class="custom-control-input" id="consensoSwitch"
                 wire:model.live="consensoMarketing"
                 wire:change="aggiornaConsenso">
          <label class="custom-control-label" for="consensoSwitch">
            Consenso marketing
            @if ($cliente->consenso_marketing_at)
              <small class="text-muted">({{ $cliente->consenso_marketing_at->format('d/m/Y') }})</small>
            @endif
          </label>
        </div>
        @endrole
      </div>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-2 text-center">
          <div class="text-muted small">Valore lifetime</div>
          <div class="font-weight-bold">€ {{ number_format($cliente->valore_lifetime, 2, ',', '.') }}</div>
        </div>
        <div class="col-md-2 text-center">
          <div class="text-muted small">N. visite</div>
          <div class="font-weight-bold">{{ $cliente->numero_visite }}</div>
        </div>
        <div class="col-md-2 text-center">
          <div class="text-muted small">Ultima visita</div>
          <div class="font-weight-bold">{{ $cliente->ultima_visita_at?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="col-md-2 text-center">
          <div class="text-muted small">Prima visita</div>
          <div class="font-weight-bold">{{ $cliente->created_at->format('d/m/Y') }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small mb-1">Spesa mensile (12 mesi)</div>
          <canvas id="sparklineSpesa" height="50"></canvas>
        </div>
      </div>

      @role('admin')
      {{-- Form nuova nota CRM --}}
      <div class="card card-outline card-secondary mb-3">
        <div class="card-header p-2"><h4 class="card-title text-sm">Aggiungi nota</h4></div>
        <div class="card-body p-2">
          <div class="row">
            <div class="col-md-2">
              <select wire:model="tipoNota" class="form-control form-control-sm">
                <option value="nota">📝 Nota</option>
                <option value="chiamata">📞 Chiamata</option>
                <option value="email">✉️ Email</option>
                <option value="appuntamento">📅 Appuntamento</option>
                <option value="altro">⚬ Altro</option>
              </select>
            </div>
            <div class="col-md-8">
              <textarea wire:model="nuovaNota" class="form-control form-control-sm @error('nuovaNota') is-invalid @enderror"
                        rows="2" placeholder="Testo nota..."></textarea>
              @error('nuovaNota') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2">
              <button wire:click="aggiungiNota" class="btn btn-primary btn-sm w-100">
                <i class="fas fa-plus"></i> Aggiungi
              </button>
            </div>
          </div>
        </div>
      </div>
      @endrole

      {{-- Timeline note CRM --}}
      <div class="timeline timeline-inverse">
        @forelse ($crmNote as $nota)
        <div class="time-label">
          <span class="bg-secondary">{{ $nota->data_interazione->format('d/m/Y') }}</span>
        </div>
        <div>
          <i class="{{ $nota->tipo->icona() }} bg-secondary"></i>
          <div class="timeline-item">
            <span class="time"><i class="fas fa-clock"></i> {{ $nota->data_interazione->format('H:i') }}</span>
            <h3 class="timeline-header">{{ $nota->tipo->label() }} — {{ $nota->user->name }}</h3>
            <div class="timeline-body">{{ $nota->testo }}</div>
            @role('admin')
            <div class="timeline-footer">
              <button wire:click="eliminaNota({{ $nota->id }})"
                      wire:confirm="Eliminare questa nota?"
                      class="btn btn-xs btn-danger">
                <i class="fas fa-trash"></i>
              </button>
            </div>
            @endrole
          </div>
        </div>
        @empty
        <p class="text-muted">Nessuna nota CRM.</p>
        @endforelse
      </div>

      {{-- Storico campagne --}}
      @if ($campagneRicevute->isNotEmpty())
      <hr>
      <h5>Campagne email ricevute</h5>
      <table class="table table-xs table-sm">
        <thead><tr><th>Campagna</th><th>Stato</th><th>Data</th></tr></thead>
        <tbody>
          @foreach ($campagneRicevute as $inv)
          <tr>
            <td>{{ $inv->campagna->nome ?? '—' }}</td>
            <td><span class="badge {{ $inv->stato === 'inviata' ? 'badge-success' : 'badge-secondary' }}">{{ $inv->stato }}</span></td>
            <td>{{ $inv->inviata_at?->format('d/m/Y') ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    </div>
  </div>

  <!-- Modal Associa Veicolo -->
  @if($showAssociaModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Associa Veicolo</h5>
          <button type="button" class="close" wire:click="$set('showAssociaModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Cerca veicolo per targa/marca/modello</label>
            <input wire:model.live.debounce.300ms="searchVeicolo" type="text"
              class="form-control" placeholder="Es: FZ123AB, Fiat Panda...">
          </div>
          @error('veicoloSelezionatoId')<div class="alert alert-danger py-1">{{ $message }}</div>@enderror

          @foreach($veicoliDisponibili as $v)
          <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <div>
              <strong>{{ $v->marca }} {{ $v->modello }}</strong>
              @if($v->targa) <span class="badge badge-secondary">{{ $v->targa }}</span> @endif
            </div>
            <button wire:click="$set('veicoloSelezionatoId', {{ $v->id }})"
              class="btn btn-sm {{ $veicoloSelezionatoId == $v->id ? 'btn-success' : 'btn-outline-success' }}">
              {{ $veicoloSelezionatoId == $v->id ? '✓ Selezionato' : 'Seleziona' }}
            </button>
          </div>
          @endforeach
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showAssociaModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="associa" wire:loading.attr="disabled">
            Associa
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
