<div>
  @if(session('success'))
  <div class="alert alert-success alert-dismissible py-2">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  {{-- Form Upload --}}
  <div class="card card-outline card-secondary mb-3">
    <div class="card-header py-2">
      <h3 class="card-title text-sm"><i class="fas fa-camera mr-1"></i>Carica Foto</h3>
    </div>
    <div class="card-body">
      <div class="row align-items-end">
        <div class="col-md-4">
          <div class="form-group mb-0">
            <label class="small">File (immagini, max 10MB)</label>
            <input type="file" wire:model="nuoveFoto" multiple accept="image/*"
              class="form-control-file @error('nuoveFoto.*') is-invalid @enderror">
            @error('nuoveFoto.*')<div class="text-danger small">{{ $message }}</div>@enderror
            @if($nuoveFoto)
            <div wire:loading wire:target="nuoveFoto" class="text-muted small">
              <span class="spinner-border spinner-border-sm"></span> Caricamento...
            </div>
            @endif
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group mb-0">
            <label class="small">Fase</label>
            <select wire:model="uploadFase" class="form-control form-control-sm">
              @foreach($fasiFoto as $fase)
              <option value="{{ $fase->value }}">{{ $fase->label() }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group mb-0">
            <label class="small">Descrizione</label>
            <input type="text" wire:model="uploadDescrizione" class="form-control form-control-sm"
              placeholder="Opzionale...">
          </div>
        </div>
        <div class="col-md-2">
          <button wire:click="uploadFoto" class="btn btn-sm btn-primary w-100"
            wire:loading.attr="disabled" wire:target="uploadFoto">
            <span wire:loading wire:target="uploadFoto" class="spinner-border spinner-border-sm mr-1"></span>
            <i class="fas fa-upload mr-1"></i> Carica
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Filtri + Download ZIP --}}
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <select wire:model.live="filtroFase" class="form-control form-control-sm d-inline-block w-auto">
        <option value="">Tutte le fasi</option>
        @foreach($fasiFoto as $fase)
        <option value="{{ $fase->value }}">{{ $fase->label() }}</option>
        @endforeach
      </select>
      <span class="ml-2 text-muted small">{{ $foto->count() }} foto</span>
    </div>
    @if($foto->count() > 0)
    <a href="{{ route('carrozzeria.foto.zip', request()->segment(2)) }}"
      class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-file-archive mr-1"></i> Scarica ZIP
    </a>
    @endif
  </div>

  {{-- Galleria --}}
  <div x-data="{ lightbox: false, src: '', nome: '' }">
    <div class="row">
      @forelse($foto as $f)
      <div class="col-md-3 col-sm-4 col-6 mb-3">
        <div class="card h-100">
          <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
            style="height:140px;overflow:hidden;cursor:pointer"
            @click="lightbox=true; src='{{ route('allegati.foto-danni', basename($f->percorso)) }}'; nome='{{ addslashes($f->nome_file) }}'">
            <img src="{{ route('allegati.foto-danni', basename($f->percorso)) }}"
              alt="{{ $f->nome_file }}"
              style="max-height:140px;max-width:100%;object-fit:cover"
              loading="lazy">
          </div>
          <div class="card-body p-2">
            <span class="badge {{ $f->fase->badgeClass() }} d-block mb-1">{{ $f->fase->label() }}</span>
            @if($f->descrizione)
            <small class="text-muted">{{ Str::limit($f->descrizione, 40) }}</small>
            @endif
            <small class="text-muted d-block">{{ $f->created_at->format('d/m/Y H:i') }}</small>
          </div>
          <div class="card-footer p-1 text-right">
            <button wire:click="eliminaFoto({{ $f->id }})"
              wire:confirm="Eliminare questa foto?"
              class="btn btn-xs btn-outline-danger">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
      @empty
      <div class="col-12 text-center text-muted py-4">
        <i class="fas fa-camera fa-2x mb-2"></i>
        <p>Nessuna foto caricata{{ $filtroFase ? ' per questa fase' : '' }}.</p>
      </div>
      @endforelse
    </div>

    {{-- Lightbox --}}
    <dialog x-ref="dlg" x-show="lightbox" @click.self="lightbox=false"
      style="border:none;background:rgba(0,0,0,.85);max-width:90vw;max-height:90vh;padding:0;display:flex;align-items:center;justify-content:center">
      <div class="text-center">
        <img :src="src" :alt="nome" style="max-width:85vw;max-height:80vh;object-fit:contain">
        <div class="mt-2">
          <a :href="src" :download="nome" class="btn btn-sm btn-light mr-2">
            <i class="fas fa-download mr-1"></i> Scarica
          </a>
          <button class="btn btn-sm btn-secondary" @click="lightbox=false">Chiudi</button>
        </div>
      </div>
    </dialog>
    <template x-if="lightbox">
      <div @click="lightbox=false"
        style="position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;display:flex;align-items:center;justify-content:center">
        <div class="text-center" @click.stop>
          <img :src="src" :alt="nome" style="max-width:85vw;max-height:80vh;object-fit:contain">
          <div class="mt-2">
            <a :href="src" :download="nome" class="btn btn-sm btn-light mr-2">
              <i class="fas fa-download mr-1"></i> Scarica
            </a>
            <button class="btn btn-sm btn-secondary" @click="lightbox=false">Chiudi</button>
          </div>
        </div>
      </div>
    </template>
  </div>
</div>
