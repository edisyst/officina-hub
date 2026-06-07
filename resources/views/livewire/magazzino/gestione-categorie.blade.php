<div>
  <div class="d-flex justify-content-end mb-3">
    <button wire:click="apriModal()" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> Nuova categoria
    </button>
  </div>

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('error') }}
  </div>
  @endif

  <div class="row" wire:sortable="aggiornaOrdinamento">
    @forelse($categorie as $cat)
    <div class="col-md-4 mb-3" wire:sortable.item="{{ $cat->id }}" wire:key="cat-{{ $cat->id }}">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span wire:sortable.handle style="cursor:grab" class="mr-2">
            <i class="fas fa-grip-vertical text-muted"></i>
          </span>
          <strong class="flex-grow-1">{{ $cat->nome }}</strong>
          <div>
            <button wire:click="apriModal({{ $cat->id }})" class="btn btn-xs btn-info">
              <i class="fas fa-edit"></i>
            </button>
            <button wire:click="elimina({{ $cat->id }})"
              wire:confirm="Eliminare «{{ $cat->nome }}»?"
              class="btn btn-xs btn-danger">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        @if($cat->descrizione)
        <div class="card-body py-2">
          <small class="text-muted">{{ $cat->descrizione }}</small>
        </div>
        @endif
        @if($cat->figli->isNotEmpty())
        <ul class="list-group list-group-flush">
          @foreach($cat->figli as $figlio)
          <li class="list-group-item d-flex justify-content-between align-items-center py-1">
            <small>{{ $figlio->nome }}</small>
            <div>
              <button wire:click="apriModal({{ $figlio->id }})" class="btn btn-xs btn-info">
                <i class="fas fa-edit"></i>
              </button>
              <button wire:click="elimina({{ $figlio->id }})"
                wire:confirm="Eliminare «{{ $figlio->nome }}»?"
                class="btn btn-xs btn-danger">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </li>
          @endforeach
        </ul>
        @endif
        <div class="card-footer py-1">
          <button wire:click="apriModal(null, {{ $cat->id }})" class="btn btn-xs btn-outline-secondary">
            <i class="fas fa-plus"></i> Sottocategoria
          </button>
        </div>
      </div>
    </div>
    @empty
    <div class="col-12 text-center text-muted py-5">Nessuna categoria. Creane una.</div>
    @endforelse
  </div>

  <!-- Modal Categoria -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica categoria' : 'Nuova categoria' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          @if($parent_id)
          <div class="alert alert-info py-2">Sottocategoria di: {{ \App\Models\CategoriaArticolo::find($parent_id)?->nome }}</div>
          @endif
          <div class="form-group">
            <label>Nome *</label>
            <input wire:model="nome" type="text" class="form-control @error('nome') is-invalid @enderror">
            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Descrizione</label>
            <input wire:model="descrizione" type="text" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal',false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salva" wire:loading.attr="disabled">
            <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
