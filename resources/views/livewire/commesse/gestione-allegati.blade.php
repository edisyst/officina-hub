<div>
  @can('update', $commessa)
  <div class="card card-body bg-light mb-3">
    <h6 class="mb-2">Carica Allegati</h6>
    <div class="form-group mb-2">
      <input wire:model="files" type="file" multiple class="form-control-file">
      @error('files.*')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="form-group mb-2">
      <input wire:model="descrizione" type="text" class="form-control form-control-sm"
        placeholder="Descrizione (opzionale)">
    </div>
    <button wire:click="upload" wire:loading.attr="disabled" class="btn btn-sm btn-success">
      <span wire:loading wire:target="upload" class="spinner-border spinner-border-sm mr-1"></span>
      <i class="fas fa-upload"></i> Carica
    </button>
  </div>
  @endcan

  @if($allegati->isEmpty())
  <p class="text-muted text-center py-3">Nessun allegato.</p>
  @else
  <div class="row">
    @foreach($allegati as $allegato)
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="card h-100">
        @if($allegato->isImmagine())
        <img src="{{ route('allegati.download', $allegato->id) }}" class="card-img-top"
          style="height:120px;object-fit:cover" alt="{{ $allegato->nome_file }}"
          onerror="this.style.display='none'">
        @else
        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:80px">
          <i class="fas fa-file fa-2x text-muted"></i>
        </div>
        @endif
        <div class="card-body p-2">
          <p class="card-text small text-truncate mb-0" title="{{ $allegato->nome_file }}">
            {{ $allegato->nome_file }}
          </p>
          @if($allegato->descrizione)
          <small class="text-muted">{{ $allegato->descrizione }}</small>
          @endif
          <small class="d-block text-muted">{{ round($allegato->dimensione_bytes / 1024) }} KB</small>
        </div>
        <div class="card-footer p-1 d-flex justify-content-between">
          <a href="{{ route('allegati.download', $allegato->id) }}" class="btn btn-xs btn-outline-primary" download>
            <i class="fas fa-download"></i>
          </a>
          @can('update', $commessa)
          <button wire:click="elimina({{ $allegato->id }})"
            wire:confirm="Eliminare '{{ $allegato->nome_file }}'?"
            class="btn btn-xs btn-outline-danger">
            <i class="fas fa-trash"></i>
          </button>
          @endcan
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>
