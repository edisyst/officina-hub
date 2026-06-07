<div>
  {{-- Barra strumenti --}}
  <div class="d-flex justify-content-end mb-3 gap-2">
    <button wire:click="$toggle('showQrScanner')"
            class="btn btn-outline-secondary btn-tablet">
      <i class="fas fa-qrcode mr-1"></i>
      {{ $showQrScanner ? 'Chiudi scanner' : 'Scansiona QR' }}
    </button>
  </div>

  {{-- Scanner QR (jsQR via CDN) --}}
  @if($showQrScanner)
  <div class="card mb-3"
       x-data="{
         scanning: false,
         error: '',
         stream: null,
         animFrame: null,
         canvas: null,
         ctx: null,
         video: null,
         async startScan() {
           this.error = '';
           try {
             this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
             this.video = this.$refs.video;
             this.canvas = this.$refs.canvas;
             this.ctx = this.canvas.getContext('2d');
             this.video.srcObject = this.stream;
             await this.video.play();
             this.scanning = true;
             this.tick();
           } catch(e) { this.error = 'Fotocamera non disponibile: ' + e.message; }
         },
         stopScan() {
           this.scanning = false;
           if (this.animFrame) cancelAnimationFrame(this.animFrame);
           if (this.stream) this.stream.getTracks().forEach(t => t.stop());
         },
         tick() {
           if (!this.scanning) return;
           if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
             this.canvas.width  = this.video.videoWidth;
             this.canvas.height = this.video.videoHeight;
             this.ctx.drawImage(this.video, 0, 0);
             const img = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
             const code = jsQR(img.data, img.width, img.height);
             if (code) {
               this.stopScan();
               window.location.href = code.data;
               return;
             }
           }
           this.animFrame = requestAnimationFrame(() => this.tick());
         }
       }"
       x-init="startScan()"
       x-on:scan-stopped.window="stopScan()">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-camera mr-1"></i> Scanner QR</span>
      <button type="button" @click="stopScan(); $wire.set('showQrScanner', false)"
              class="btn btn-sm btn-secondary">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="card-body text-center p-2">
      <video x-ref="video" playsinline muted
             style="max-width:100%;max-height:320px;border-radius:8px;background:#000"></video>
      <canvas x-ref="canvas" style="display:none"></canvas>
      <p x-show="error" x-text="error" class="text-danger mt-2 mb-0"></p>
      <p class="text-muted mt-2 mb-0 small">Inquadra il QR code della commessa</p>
    </div>
  </div>
  @endif

  @if($errors->has('avvia'))
  <div class="alert alert-warning alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-exclamation-triangle mr-2"></i>
    {{ $errors->first('avvia') }}
  </div>
  @endif

  @forelse($commesse as $commessa)
  <div class="card card-outline card-primary mb-4">
    <div class="card-header">
      <h3 class="card-title">
        <span class="font-weight-bold">{{ $commessa->numero }}</span>
        <span class="badge {{ $commessa->stato->badgeClass() }} ml-2">{{ $commessa->stato->label() }}</span>
        <span class="ml-3 text-muted">{{ $commessa->veicolo->targa ?? '—' }}</span>
        <span class="ml-2">{{ $commessa->cliente->nome_completo }}</span>
      </h3>
      <div class="card-tools">
        <a href="{{ route('commesse.show', $commessa->id) }}" class="btn btn-xs btn-outline-secondary" target="_blank">
          <i class="fas fa-external-link-alt"></i> Commessa
        </a>
      </div>
    </div>
    <div class="card-body p-0">
      @forelse($commessa->lavorazioni as $lav)
      <div class="d-flex align-items-center border-bottom px-3 py-2"
        x-data="{
          attiva: {{ $lav->is_attiva ? 'true' : 'false' }},
          startTs: {{ $lav->started_at ? $lav->started_at->timestamp * 1000 : 'null' }},
          timer: '--:--:--',
          interval: null,
          tick() {
            if (!this.startTs) { this.timer = '--:--:--'; return; }
            const elapsed = Math.floor((Date.now() - this.startTs) / 1000);
            const h = String(Math.floor(elapsed / 3600)).padStart(2, '0');
            const m = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
            const s = String(elapsed % 60).padStart(2, '0');
            this.timer = h + ':' + m + ':' + s;
          },
          init() {
            if (this.attiva) {
              this.tick();
              this.interval = setInterval(() => this.tick(), 1000);
            }
          }
        }"
      >
        <!-- Descrizione + timer -->
        <div class="flex-grow-1">
          <div class="font-weight-bold">{{ $lav->descrizione }}</div>
          <div class="small text-muted">
            @if($lav->ponte) <i class="fas fa-car-side mr-1"></i>{{ $lav->ponte->nome }} @endif
            @if($lav->meccanico) <i class="fas fa-user-cog ml-2 mr-1"></i>{{ $lav->meccanico->name }} @endif
          </div>

          <!-- Timer live -->
          <div x-show="attiva" class="mt-1">
            <span class="badge badge-lg"
              :class="{
                'badge-danger': startTs && Math.floor((Date.now() - startTs) / 60000) > {{ $lav->minuti_preventivati }},
                'badge-primary': !startTs || Math.floor((Date.now() - startTs) / 60000) <= {{ $lav->minuti_preventivati }}
              }"
              style="font-size:1.1rem;letter-spacing:1px"
              x-text="timer">
            </span>
            <small class="text-muted ml-2">/ {{ $lav->minuti_preventivati }} min prev.</small>
          </div>

          <!-- Se fermata: mostra risultato -->
          @if($lav->stopped_at && $lav->minuti_effettivi !== null)
          <div class="mt-1">
            <span class="badge {{ $lav->minuti_effettivi <= $lav->minuti_preventivati ? 'badge-success' : 'badge-danger' }}">
              {{ $lav->minuti_effettivi }} min effettivi
            </span>
            <small class="text-muted ml-1">
              (prev: {{ $lav->minuti_preventivati }} min,
              delta: {{ $lav->delta_minuti >= 0 ? '+' : '' }}{{ $lav->delta_minuti }})
            </small>
          </div>
          @endif

          @if($lav->note)
          <small class="text-muted d-block mt-1"><i class="fas fa-sticky-note mr-1"></i>{{ $lav->note }}</small>
          @endif
        </div>

        <!-- Pulsanti -->
        <div class="ml-3 d-flex align-items-center" style="gap:8px">
          @if(! $lav->is_attiva && ! $lav->stopped_at)
          <button
            wire:click="avvia({{ $lav->id }})"
            wire:loading.attr="disabled"
            class="btn btn-success btn-lg"
            style="min-width:90px">
            <i class="fas fa-play"></i> START
          </button>
          @elseif($lav->is_attiva)
          <button
            wire:click="ferma({{ $lav->id }})"
            wire:loading.attr="disabled"
            x-on:click="attiva = false; clearInterval(interval)"
            class="btn btn-danger btn-lg"
            style="min-width:90px">
            <i class="fas fa-stop"></i> STOP
          </button>
          <button wire:click="apriNotaModal({{ $lav->id }})" class="btn btn-outline-secondary">
            <i class="fas fa-sticky-note"></i>
          </button>
          @else
          <span class="badge badge-secondary">Completata</span>
          @endif
        </div>
      </div>
      @empty
      <p class="text-muted p-3 mb-0">Nessuna lavorazione su questa commessa.</p>
      @endforelse
    </div>
  </div>
  @empty
  <div class="callout callout-info">
    <h5>Nessuna commessa in lavorazione</h5>
    <p class="mb-0">Non ci sono commesse in stato "In Lavorazione" assegnate a te.</p>
  </div>
  @endforelse

  <!-- Modal nota -->
  @if($showNotaModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Aggiungi Nota</h5>
          <button type="button" class="close" wire:click="$set('showNotaModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <textarea wire:model="notaTesto" class="form-control" rows="4" placeholder="Note sulla lavorazione..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showNotaModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salvaNota">Salva Nota</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
