<div>
  <!-- Toolbar -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="row align-items-center">
        <div class="col-md-4">
          <input wire:model.live.debounce.300ms="search" type="text"
            class="form-control form-control-sm" placeholder="Cerca numero, cliente, targa...">
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroStato" class="form-control form-control-sm">
            <option value="">Tutti gli stati</option>
            @foreach($stati as $s)
            <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroTipo" class="form-control form-control-sm">
            <option value="">Tutti i tipi</option>
            @foreach($tipi as $t)
            <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <div class="btn-group btn-group-sm">
            <button wire:click="$set('vista', 'tabella')"
              class="btn {{ $vista === 'tabella' ? 'btn-primary' : 'btn-outline-primary' }}">
              <i class="fas fa-list"></i>
            </button>
            <button wire:click="$set('vista', 'kanban')"
              class="btn {{ $vista === 'kanban' ? 'btn-primary' : 'btn-outline-primary' }}">
              <i class="fas fa-columns"></i>
            </button>
          </div>
        </div>
        <div class="col-md-2 text-right">
          @can('create', \App\Models\Commessa::class)
          <a href="{{ route('commesse.create') }}" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Nuova Commessa
          </a>
          @endcan
        </div>
      </div>
    </div>
  </div>

  <!-- Vista Tabella -->
  @if($vista === 'tabella')
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Numero</th><th>Cliente</th><th>Veicolo</th><th>Tipo</th>
            <th>Stato</th><th>Ingresso</th><th>Uscita Prevista</th>
          </tr>
        </thead>
        <tbody>
          @forelse($commesse as $c)
          <tr>
            <td><a href="{{ route('commesse.show', $c->id) }}" class="font-weight-bold">{{ $c->numero }}</a></td>
            <td>{{ $c->cliente->nome_completo }}</td>
            <td>
              <strong>{{ $c->veicolo->targa ?? '-' }}</strong>
              <br><small>{{ $c->veicolo->marca }} {{ $c->veicolo->modello }}</small>
            </td>
            <td>{{ $c->tipo->label() }}</td>
            <td><span class="badge {{ $c->stato->badgeClass() }}">{{ $c->stato->label() }}</span></td>
            <td>{{ $c->data_ingresso->format('d/m/Y') }}</td>
            <td>
              @if($c->data_uscita_prevista)
                @if($c->data_uscita_prevista->isPast() && ! in_array($c->stato->value, ['consegnata','fatturata']))
                  <span class="text-danger">{{ $c->data_uscita_prevista->format('d/m/Y') }} <i class="fas fa-exclamation-triangle"></i></span>
                @else
                  {{ $c->data_uscita_prevista->format('d/m/Y') }}
                @endif
              @else
                -
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center text-muted py-3">Nessuna commessa trovata.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $commesse->links() }}</div>
  </div>
  @endif

  <!-- Vista Kanban -->
  @if($vista === 'kanban')
  <div class="d-flex overflow-auto pb-3" style="gap:12px">
    @foreach($stati as $stato)
    <div class="flex-shrink-0" style="width:280px">
      <div class="card mb-0">
        <div class="card-header py-2">
          <span class="badge {{ $stato->badgeClass() }} mr-1">{{ count($commessePerStato[$stato->value]) }}</span>
          <strong>{{ $stato->label() }}</strong>
        </div>
        <div class="card-body p-2" style="max-height:70vh;overflow-y:auto">
          @forelse($commessePerStato[$stato->value] as $c)
          <div class="card card-body py-2 px-3 mb-2 shadow-sm">
            <div class="d-flex justify-content-between">
              <a href="{{ route('commesse.show', $c->id) }}" class="font-weight-bold text-sm">{{ $c->numero }}</a>
              <small class="text-muted">{{ $c->data_ingresso->format('d/m') }}</small>
            </div>
            <small><strong>{{ $c->veicolo->targa ?? '-' }}</strong> {{ $c->veicolo->marca }} {{ $c->veicolo->modello }}</small>
            <small class="text-muted d-block">{{ $c->cliente->nome_completo }}</small>
          </div>
          @empty
          <p class="text-muted text-sm text-center mt-2">Nessuna commessa</p>
          @endforelse
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>
