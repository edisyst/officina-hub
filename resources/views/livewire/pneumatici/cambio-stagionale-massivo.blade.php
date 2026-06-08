<div>
  {{-- Filtri --}}
  <div class="card card-outline card-primary">
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group mb-0">
            <label>Stagione da montare</label>
            <select wire:model.live="filtraStagioneTarget" class="form-control">
              @foreach($stagioni as $val => $lbl)
                <option value="{{ $val }}">{{ $lbl }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group mb-0">
            <label>Cerca cliente / targa</label>
            <input wire:model.live.debounce.300ms="filtroCliente" type="text"
                   class="form-control" placeholder="Nome, cognome, targa...">
          </div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <span class="text-muted small">
            Mostra clienti con <strong>{{ $filtraStagioneTarget === 'estivo' ? 'invernali' : 'estivi' }}</strong>
            in deposito
          </span>
        </div>
      </div>
    </div>
  </div>

  {{-- Azioni massiva --}}
  <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
    <div class="form-check mr-3">
      <input type="checkbox" class="form-check-input" id="checkTutti"
             wire:change="selezionaTutti($event.target.checked)"
             {{ $tuttiSelezionati ? 'checked' : '' }}>
      <label class="form-check-label" for="checkTutti">Seleziona tutti</label>
    </div>
    <button wire:click="inviaNotifiche"
            class="btn btn-sm btn-warning mr-2 {{ empty($selezionati) ? 'disabled' : '' }}">
      <i class="fas fa-envelope"></i> Invia notifica ({{ count($selezionati) }})
    </button>
    <button wire:click="apriModalAppuntamenti"
            class="btn btn-sm btn-success {{ empty($selezionati) ? 'disabled' : '' }}">
      <i class="fas fa-calendar-plus"></i> Crea appuntamenti in blocco ({{ count($selezionati) }})
    </button>
    @if(count($selezionati) > 0)
    <form method="POST" action="{{ route('deposito.etichette-multiple') }}" target="_blank">
      @csrf
      @foreach($selezionati as $sid)
        <input type="hidden" name="ids[]" value="{{ $sid }}">
      @endforeach
      <button type="submit" class="btn btn-sm btn-secondary">
        <i class="fas fa-tags"></i> Stampa etichette
      </button>
    </form>
    @endif
  </div>

  {{-- Tabella --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead class="thead-light">
            <tr>
              <th width="30"></th>
              <th>Cliente</th>
              <th>Targa</th>
              <th>Set in deposito</th>
              <th>Ubicazione</th>
              <th>Deposito da</th>
              <th>Ultima notifica</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pneumatici as $p)
            @php $ult = $p->movimenti->first(); @endphp
            <tr>
              <td class="text-center">
                <input type="checkbox"
                       wire:click="toggleSelezionato({{ $p->id }})"
                       {{ in_array($p->id, $selezionati) ? 'checked' : '' }}>
              </td>
              <td>{{ $p->cliente?->nome_completo ?? '—' }}</td>
              <td>
                @if($p->veicolo)
                  <a href="{{ route('veicoli.show', $p->veicolo_id) }}">{{ $p->veicolo->targa }}</a>
                @endif
              </td>
              <td>
                <span class="badge {{ $p->stagione->badgeClass() }}">{{ $p->stagione->label() }}</span>
                {{ $p->marca }} {{ $p->misura }}
              </td>
              <td><small>{{ $ult?->ubicazione ?? '—' }}</small></td>
              <td>
                @if($ult)
                  {{ $ult->data_azione->format('d/m/Y') }}
                  <small class="text-muted">({{ $ult->data_azione->diffForHumans() }})</small>
                @else —
                @endif
              </td>
              <td>
                @if($p->notifica_inviata_at)
                  <small>{{ \Carbon\Carbon::parse($p->notifica_inviata_at)->format('d/m/Y') }}</small>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-3">
              Nessun cliente con set della stagione opposta in deposito.
            </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer">
      {{ $pneumatici->links() }}
    </div>
  </div>

  {{-- Modal creazione appuntamenti in blocco --}}
  @if($showModalAppuntamenti)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Crea appuntamenti in blocco</h5>
          <button wire:click="$set('showModalAppuntamenti', false)" class="close"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <p>
            Verranno creati <strong>{{ count($selezionati) }}</strong> appuntamenti distribuiti nella settimana
            selezionata, bilanciando il carico giornaliero.
          </p>
          <div class="form-group">
            <label>Settimana di riferimento</label>
            <input wire:model="settimanaScelta" type="date" class="form-control @error('settimanaScelta') is-invalid @enderror">
            @error('settimanaScelta')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Ora inizio appuntamento</label>
                <input wire:model="oraInizio" type="time" class="form-control @error('oraInizio') is-invalid @enderror">
                @error('oraInizio')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Durata (minuti)</label>
                <input wire:model="durataMinuti" type="number" min="15" max="480"
                       class="form-control @error('durataMinuti') is-invalid @enderror">
                @error('durataMinuti')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showModalAppuntamenti', false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="creaAppuntamentiInBlocco" class="btn btn-success">
            <i class="fas fa-calendar-plus"></i> Crea appuntamenti
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
