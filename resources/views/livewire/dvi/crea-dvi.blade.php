<div>
  @if(session('success'))
  <div class="alert alert-success alert-dismissible mb-3">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif
  @if(session('error'))
  <div class="alert alert-danger alert-dismissible mb-3">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('error') }}
  </div>
  @endif

  <!-- Header -->
  <div class="card card-primary card-outline mb-3">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-camera-retro mr-2"></i>
        DVI — {{ $commessa->numero }}
        <span class="badge badge-secondary ml-2">{{ $commessa->veicolo?->targa }}</span>
        <span class="badge badge-light ml-1">{{ $commessa->cliente->nome_completo }}</span>
      </h3>
      <div class="card-tools">
        @if($ispezione)
        <a href="{{ route('dvi.anteprima', $ispezione->id) }}" class="btn btn-sm btn-outline-info" target="_blank">
          <i class="fas fa-eye mr-1"></i>Anteprima cliente
        </a>
        <button wire:click="invia" wire:confirm="Inviare la DVI al cliente? Il link sarà valido 7 giorni."
          class="btn btn-sm btn-success ml-1"
          @if(!$ispezione || $ispezione->voci->isEmpty()) disabled @endif>
          <span wire:loading wire:target="invia" class="spinner-border spinner-border-sm mr-1"></span>
          <i class="fas fa-paper-plane mr-1"></i>Invia al cliente
        </button>
        @endif
      </div>
    </div>
  </div>

  <!-- Voci DVI -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-list mr-2"></i>Voci ispezione</h3>
      <div class="card-tools">
        <button wire:click="apriFormVoce" class="btn btn-sm btn-primary">
          <i class="fas fa-plus mr-1"></i>Aggiungi voce
        </button>
      </div>
    </div>
    <div class="card-body p-0">

      <!-- Form inline nuova/modifica voce -->
      @if($showFormVoce)
      <div class="p-3 bg-light border-bottom">
        <h6 class="mb-3">{{ $editingVoceId ? 'Modifica voce' : 'Nuova voce' }}</h6>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>Categoria <span class="text-danger">*</span></label>
              <input type="text" wire:model="vCategoria"
                class="form-control @error('vCategoria') is-invalid @enderror"
                list="categorie-list"
                placeholder="Scegli o digita...">
              <datalist id="categorie-list">
                @foreach($categoriePredefinite as $cat)
                <option value="{{ $cat }}">
                @endforeach
              </datalist>
              @error('vCategoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>Descrizione <span class="text-danger">*</span></label>
              <input type="text" wire:model="vDescrizone"
                class="form-control @error('vDescrizone') is-invalid @enderror"
                placeholder="Es. Pastiglie freno anteriore consumate">
              @error('vDescrizone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>Prezzo stimato (€)</label>
              <input type="number" wire:model="vPrezzoStimato"
                class="form-control" step="0.01" min="0" placeholder="0.00">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>Urgenza <span class="text-danger">*</span></label>
              <div class="d-flex" style="gap:.5rem">
                <button type="button"
                  wire:click="$set('vUrgenza','ok')"
                  class="btn flex-fill {{ $vUrgenza === 'ok' ? 'btn-success' : 'btn-outline-success' }}"
                  style="height:56px;font-size:1.1rem">
                  <i class="fas fa-check-circle mr-1"></i>OK
                </button>
                <button type="button"
                  wire:click="$set('vUrgenza','attenzione')"
                  class="btn flex-fill {{ $vUrgenza === 'attenzione' ? 'btn-warning' : 'btn-outline-warning' }}"
                  style="height:56px;font-size:1.1rem">
                  <i class="fas fa-exclamation-triangle mr-1"></i>Attenzione
                </button>
                <button type="button"
                  wire:click="$set('vUrgenza','urgente')"
                  class="btn flex-fill {{ $vUrgenza === 'urgente' ? 'btn-danger' : 'btn-outline-danger' }}"
                  style="height:56px;font-size:1.1rem">
                  <i class="fas fa-times-circle mr-1"></i>Urgente
                </button>
              </div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="form-group">
              <label>Note</label>
              <textarea wire:model="vNote" class="form-control" rows="2"
                placeholder="Note tecniche opzionali..."></textarea>
            </div>
          </div>
        </div>
        <div class="d-flex" style="gap:.5rem">
          <button wire:click="salvaVoce" class="btn btn-primary">
            <span wire:loading wire:target="salvaVoce" class="spinner-border spinner-border-sm mr-1"></span>
            <i class="fas fa-save mr-1"></i>Salva voce
          </button>
          <button wire:click="$set('showFormVoce',false)" class="btn btn-secondary">Annulla</button>
        </div>
      </div>
      @endif

      <!-- Lista voci -->
      @if($ispezione && $ispezione->voci->count())
      <div id="voci-sortable">
        @foreach($ispezione->voci as $voce)
        <div class="p-3 border-bottom" data-voce-id="{{ $voce->id }}">
          <div class="d-flex align-items-start justify-content-between">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center mb-1">
                <i class="fas fa-grip-vertical text-muted mr-2" style="cursor:grab"></i>
                <span class="badge {{ $voce->urgenza->badgeClass() }} mr-2">
                  <i class="{{ $voce->urgenza->icon() }} mr-1"></i>{{ $voce->urgenza->label() }}
                </span>
                <strong>{{ $voce->categoria }}</strong>
                <span class="text-muted mx-2">—</span>
                {{ $voce->descrizione }}
                @if($voce->prezzo_stimato)
                <span class="badge badge-light ml-2">€ {{ number_format($voce->prezzo_stimato, 2, ',', '.') }}</span>
                @endif
              </div>
              @if($voce->note)
              <small class="text-muted d-block ml-4">{{ $voce->note }}</small>
              @endif

              <!-- Media della voce -->
              <div class="ml-4 mt-2">
                <div class="d-flex flex-wrap" style="gap:.5rem">
                  @foreach($voce->media as $media)
                  <div class="position-relative" style="width:80px">
                    @if($media->tipo->value === 'foto')
                    <img src="{{ route('dvi.media', $media->id) }}"
                      alt="foto" class="img-thumbnail"
                      style="width:80px;height:60px;object-fit:cover;cursor:pointer"
                      onclick="window.open('{{ route('dvi.media', $media->id) }}','_blank')">
                    @else
                    <a href="{{ route('dvi.media', $media->id) }}" target="_blank"
                      class="d-block position-relative">
                      <img src="{{ route('dvi.media.thumb', $media->id) }}"
                        alt="video" class="img-thumbnail"
                        style="width:80px;height:60px;object-fit:cover">
                      <span class="position-absolute" style="top:50%;left:50%;transform:translate(-50%,-50%)">
                        <i class="fas fa-play-circle fa-2x text-white" style="text-shadow:0 0 4px rgba(0,0,0,.8)"></i>
                      </span>
                    </a>
                    @endif
                    <button wire:click="eliminaMedia({{ $media->id }})"
                      wire:confirm="Eliminare questo media?"
                      class="btn btn-xs btn-danger position-absolute" style="top:0;right:0;padding:1px 4px">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  @endforeach
                </div>

                <!-- Upload foto -->
                <div class="mt-2 d-flex align-items-center" style="gap:.5rem">
                  <label class="btn btn-sm btn-outline-secondary mb-0">
                    <i class="fas fa-camera mr-1"></i>Foto
                    <input type="file" accept="image/*" capture="environment"
                      wire:model="fotoUpload"
                      x-on:change="$wire.set('fotoVoceId', {{ $voce->id }})"
                      style="display:none">
                  </label>
                  @if($fotoVoceId === $voce->id && $fotoUpload)
                  <button wire:click="uploadFoto({{ $voce->id }})" class="btn btn-sm btn-primary">
                    <span wire:loading wire:target="uploadFoto({{ $voce->id }})" class="spinner-border spinner-border-sm mr-1"></span>
                    Carica
                  </button>
                  @endif
                  @error('fotoUpload')<small class="text-danger">{{ $message }}</small>@enderror

                  <!-- Upload video tramite chunked JS -->
                  <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="avviaUploadVideo({{ $voce->id }})">
                    <i class="fas fa-video mr-1"></i>Video
                  </button>
                  <div id="progress-voce-{{ $voce->id }}" class="d-none align-items-center" style="gap:.5rem">
                    <div class="progress" style="width:120px;height:8px">
                      <div class="progress-bar" id="pb-{{ $voce->id }}" style="width:0%"></div>
                    </div>
                    <small id="pb-label-{{ $voce->id }}" class="text-muted"></small>
                  </div>
                </div>
              </div>
            </div>
            <div class="ml-2 d-flex flex-column" style="gap:.25rem">
              <button wire:click="apriFormVoce({{ $voce->id }})" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-edit"></i>
              </button>
              <button wire:click="eliminaVoce({{ $voce->id }})"
                wire:confirm="Eliminare questa voce e tutti i suoi media?"
                class="btn btn-sm btn-outline-danger">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @else
      <div class="p-4 text-center text-muted">
        <i class="fas fa-camera-retro fa-3x mb-3 d-block"></i>
        Nessuna voce ancora. Clicca "Aggiungi voce" per iniziare l'ispezione.
      </div>
      @endif
    </div>
  </div>

  <!-- Note meccanico -->
  @if($ispezione)
  <div class="card mt-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Note per il cliente</h3>
    </div>
    <div class="card-body">
      <textarea wire:model.blur="noteMeccanico"
        wire:change="aggiornaNota"
        class="form-control" rows="3"
        placeholder="Eventuali note generali da mostrare al cliente..."></textarea>
    </div>
  </div>
  @endif
</div>

@push('scripts')
<script>
const DVI_VOCE_MAX_VIDEO = 2;
const DVI_MAX_VIDEO_MB   = 100;
const DVI_CHUNK_SIZE     = 1 * 1024 * 1024; // 1MB per chunk

function avviaUploadVideo(voceId) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'video/*';
  input.capture = 'environment';
  input.onchange = function() {
    const file = this.files[0];
    if (!file) return;
    if (file.size > DVI_MAX_VIDEO_MB * 1024 * 1024) {
      alert('Il video supera il limite di ' + DVI_MAX_VIDEO_MB + 'MB.');
      return;
    }
    uploadChunked(voceId, file);
  };
  input.click();
}

function uploadChunked(voceId, file) {
  const uploadId  = crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2);
  const totalChunks = Math.ceil(file.size / DVI_CHUNK_SIZE);
  let chunkIndex = 0;

  const progressDiv = document.getElementById('progress-voce-' + voceId);
  const pb = document.getElementById('pb-' + voceId);
  const label = document.getElementById('pb-label-' + voceId);

  if (progressDiv) progressDiv.classList.remove('d-none');
  if (progressDiv) progressDiv.style.display = 'flex';

  function inviaChunk() {
    const start = chunkIndex * DVI_CHUNK_SIZE;
    const end   = Math.min(start + DVI_CHUNK_SIZE, file.size);
    const chunk = file.slice(start, end);

    const fd = new FormData();
    fd.append('chunk', chunk);
    fd.append('chunk_index', chunkIndex);
    fd.append('total_chunks', totalChunks);
    fd.append('upload_id', uploadId);
    fd.append('voce_id', voceId);
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/dvi/upload-chunk');

    xhr.onload = function() {
      if (xhr.status === 200) {
        chunkIndex++;
        const pct = Math.round((chunkIndex / totalChunks) * 100);
        if (pb) pb.style.width = pct + '%';
        if (label) label.textContent = pct + '%';

        if (chunkIndex < totalChunks) {
          inviaChunk();
        } else {
          if (progressDiv) progressDiv.classList.add('d-none');
          // Ricarica il componente Livewire per mostrare il nuovo video
          if (typeof Livewire !== 'undefined') {
            Livewire.all().forEach(c => { if (c.name && c.name.includes('crea-dvi')) c.call('$refresh'); });
          }
        }
      } else {
        if (progressDiv) progressDiv.classList.add('d-none');
        alert('Errore upload chunk ' + chunkIndex + '. Riprova.');
      }
    };

    xhr.onerror = function() {
      if (progressDiv) progressDiv.classList.add('d-none');
      alert('Errore di rete durante l\'upload.');
    };

    xhr.send(fd);
  }

  inviaChunk();
}
</script>
@endpush
