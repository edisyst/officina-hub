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
              <i class="fas fa-th-large"></i>
            </button>
            <a href="{{ route('commesse.board') }}" class="btn btn-outline-secondary" title="Board drag&drop">
              <i class="fas fa-columns"></i>
            </a>
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

  <!-- Barra azioni bulk (visibile solo con selezione attiva) -->
  @if($vista === 'tabella' && (count($selectedIds) > 0 || $selectAll))
  <div class="alert alert-info py-2 mb-3 d-flex align-items-center" style="gap:8px">
    <span>
      <strong>{{ $selectionCount }}</strong> commess{{ $selectionCount === 1 ? 'a' : 'e' }} selezionat{{ $selectionCount === 1 ? 'a' : 'e' }}.
    </span>
    @if(!$selectAll && $commesse instanceof \Illuminate\Pagination\LengthAwarePaginator && $commesse->total() > count($selectedIds))
    <button wire:click="selectAllResults" class="btn btn-sm btn-link p-0">
      Seleziona tutti i {{ $commesse->total() }} risultati
    </button>
    @endif
    <div class="ml-auto d-flex" style="gap:6px">
      @can('create', \App\Models\Commessa::class)
      <button wire:click="apriBulkStatoModal" class="btn btn-sm btn-warning">
        <i class="fas fa-exchange-alt"></i> Cambia stato
      </button>
      <button wire:click="stampaMassiva" class="btn btn-sm btn-secondary">
        <i class="fas fa-print"></i> Stampa
      </button>
      @endcan
      <button wire:click="exportCsv" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-file-csv"></i> CSV
      </button>
      <button wire:click="deselectAll" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-times"></i> Deseleziona
      </button>
    </div>
  </div>
  @endif

  <!-- Vista Tabella -->
  @if($vista === 'tabella')
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th style="width:36px">
              <input type="checkbox" wire:model.live="selectPage" title="Seleziona pagina">
            </th>
            <th>Numero</th><th>Cliente</th><th>Veicolo</th><th>Tipo</th>
            <th>Stato</th><th>Ingresso</th><th>Uscita Prevista</th>
          </tr>
        </thead>
        <tbody>
          @forelse($commesse as $c)
          <tr class="{{ in_array($c->id, $selectedIds) ? 'table-active' : '' }}">
            <td>
              <input type="checkbox" wire:model.live="selectedIds" value="{{ $c->id }}">
            </td>
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
          <tr><td colspan="8" class="text-center text-muted py-3">Nessuna commessa trovata.</td></tr>
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

  <!-- Modal cambio stato massivo -->
  @if($showBulkStatoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Cambia stato — {{ $selectionCount }} commesse</h5>
          <button type="button" class="close" wire:click="$set('showBulkStatoModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Stato target *</label>
            <select wire:model="bulkStatoTarget" class="form-control @error('bulkStatoTarget') is-invalid @enderror">
              <option value="">— Seleziona —</option>
              @foreach(\App\Enums\StatoCommessa::cases() as $s)
              <option value="{{ $s->value }}">{{ $s->label() }}</option>
              @endforeach
            </select>
            @error('bulkStatoTarget')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Nota (opzionale)</label>
            <input wire:model="bulkNota" type="text" class="form-control" placeholder="Es: accettazione massiva mattino">
          </div>
          <p class="text-warning small mb-0"><i class="fas fa-info-circle"></i> Le commesse che non possono fare la transizione verranno saltate e riportate nel report.</p>
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showBulkStatoModal',false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="eseguiBulkCambioStato" class="btn btn-warning" wire:loading.attr="disabled">
            <span wire:loading wire:target="eseguiBulkCambioStato"><i class="fas fa-spinner fa-spin"></i></span>
            Esegui
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Report bulk -->
  @if($showBulkReport && !empty($bulkReport))
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Report cambio stato</h5>
          <button type="button" class="close" wire:click="$set('showBulkReport',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <p class="text-success"><i class="fas fa-check-circle"></i> <strong>{{ count($bulkReport['success'] ?? []) }}</strong> commesse aggiornate con successo.</p>
          @if(!empty($bulkReport['skipped']))
          <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> <strong>{{ count($bulkReport['skipped']) }}</strong> saltate:</p>
          <ul class="small">
            @foreach($bulkReport['skipped'] as $skip)
            <li><strong>{{ $skip['numero'] }}</strong>: {{ $skip['motivo'] }}</li>
            @endforeach
          </ul>
          @endif
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showBulkReport',false)" class="btn btn-primary">Chiudi</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
