<div>
  @if(session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Categorie DVI</h3>
      <div class="card-tools">
        <button wire:click="apriForm" class="btn btn-sm btn-primary">
          <i class="fas fa-plus mr-1"></i>Nuova categoria
        </button>
      </div>
    </div>
    <div class="card-body p-0">

      @if($showForm)
      <div class="p-3 bg-light border-bottom">
        <h6>{{ $editingId ? 'Modifica categoria' : 'Nuova categoria' }}</h6>
        <div class="row">
          <div class="col-md-5">
            <div class="form-group">
              <label>Nome <span class="text-danger">*</span></label>
              <input type="text" wire:model="nome"
                class="form-control @error('nome') is-invalid @enderror"
                placeholder="Es. Freni">
              @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="col-md-5">
            <div class="form-group">
              <label>Icona CSS <small class="text-muted">(classe FontAwesome)</small></label>
              <input type="text" wire:model="iconaCss"
                class="form-control"
                placeholder="Es. fas fa-circle-notch">
            </div>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <div class="form-group w-100">
              @if($iconaCss)
              <label>&nbsp;</label>
              <div class="form-control text-center">
                <i class="{{ $iconaCss }}"></i>
              </div>
              @endif
            </div>
          </div>
        </div>
        <div class="d-flex" style="gap:.5rem">
          <button wire:click="salva" class="btn btn-primary btn-sm">
            <i class="fas fa-save mr-1"></i>Salva
          </button>
          <button wire:click="$set('showForm',false)" class="btn btn-secondary btn-sm">Annulla</button>
        </div>
      </div>
      @endif

      <div id="categorie-sortable">
        @foreach($categorie as $cat)
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
          data-cat-id="{{ $cat->id }}">
          <div class="d-flex align-items-center">
            <i class="fas fa-grip-vertical text-muted mr-3" style="cursor:grab"></i>
            @if($cat->icona_css)
            <i class="{{ $cat->icona_css }} mr-2 text-secondary"></i>
            @endif
            <span class="{{ !$cat->attivo ? 'text-muted text-decoration-line-through' : '' }}">
              {{ $cat->nome }}
            </span>
            @if(!$cat->attivo)
            <span class="badge badge-secondary ml-2">disattiva</span>
            @endif
          </div>
          <div class="d-flex" style="gap:.25rem">
            <button wire:click="apriForm({{ $cat->id }})" class="btn btn-xs btn-outline-secondary">
              <i class="fas fa-edit"></i>
            </button>
            <button wire:click="toggleAttivo({{ $cat->id }})" class="btn btn-xs btn-outline-{{ $cat->attivo ? 'warning' : 'success' }}">
              <i class="fas fa-{{ $cat->attivo ? 'eye-slash' : 'eye' }}"></i>
            </button>
            <button wire:click="elimina({{ $cat->id }})"
              wire:confirm="Eliminare questa categoria?"
              class="btn btn-xs btn-outline-danger">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const el = document.getElementById('categorie-sortable');
  if (el && window.Sortable) {
    Sortable.create(el, {
      handle: '.fa-grip-vertical',
      animation: 150,
      onEnd: function(evt) {
        const ordine = Array.from(el.children).map(c => parseInt(c.dataset.catId));
        Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id')).call('riordina', ordine);
      }
    });
  }
});
</script>
@endpush
