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
