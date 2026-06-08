<div>
  <div class="card mb-3">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0"><i class="fas fa-car mr-2"></i>Registrazione Rientro Veicolo di Cortesia</h4>
    </div>
    <div class="card-body">
      {{-- Riepilogo prestito --}}
      <div class="row mb-3">
        <div class="col-md-3">
          <small class="text-muted">Veicolo</small><br>
          <strong>{{ $prestito->veicolo->targa }}</strong><br>
          <span class="text-muted">{{ $prestito->veicolo->marca }} {{ $prestito->veicolo->modello }}</span>
        </div>
        <div class="col-md-3">
          <small class="text-muted">Cliente</small><br>
          <strong>{{ $prestito->cliente->nome_completo }}</strong>
        </div>
        <div class="col-md-3">
          <small class="text-muted">Km alla consegna</small><br>
          <strong>{{ number_format($prestito->km_consegna, 0, ',', '.') }} km</strong>
        </div>
        <div class="col-md-3">
          <small class="text-muted">Carburante consegna</small><br>
          <strong>{{ $prestito->carburante_consegna }}%</strong>
        </div>
      </div>

      @if($erroreKm)
        <div class="alert alert-danger">{{ $erroreKm }}</div>
      @endif
      @if($avvisoCarb)
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i>{{ $avvisoCarb }}</div>
      @endif

      <div class="row">
        <div class="col-md-5">
          <div class="form-group">
            <label>Km al rientro *</label>
            <input type="number" class="form-control @error('km_rientro') is-invalid @enderror form-control-lg"
              wire:model.live="km_rientro" min="{{ $prestito->km_consegna }}">
            @error('km_rientro')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($km_rientro >= $prestito->km_consegna)
            <small class="text-success mt-1 d-block">
              <i class="fas fa-road mr-1"></i> Km percorsi: <strong>{{ number_format($this->km_percorsi, 0, ',', '.') }}</strong>
            </small>
            @endif
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group">
            <label>Livello carburante al rientro *</label>
            <div class="d-flex align-items-center">
              <input type="range" class="form-control-range" wire:model.live="carburante_rientro"
                min="0" max="100" step="5" style="flex:1">
              <span class="ml-2 badge badge-secondary" style="font-size:1.2rem;min-width:3.5rem">{{ $carburante_rientro }}%</span>
            </div>
            <div style="height:14px;background:#eee;border-radius:7px;margin-top:5px;overflow:hidden">
              <div style="height:100%;width:{{ $carburante_rientro }}%;background:{{ $carburante_rientro > 30 ? '#28a745' : '#dc3545' }};border-radius:7px;transition:width .3s"></div>
            </div>
            @php $delta = $this->delta_carburante; @endphp
            <small class="{{ $delta >= 0 ? 'text-success' : 'text-danger' }} mt-1 d-block">
              Delta carburante: <strong>{{ $delta >= 0 ? '+' : '' }}{{ $delta }}%</strong>
            </small>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Note rientro</label>
        <textarea class="form-control" wire:model="note_rientro" rows="2"
          placeholder="Condizioni veicolo, osservazioni..."></textarea>
      </div>

      <hr>
      <p class="font-weight-bold">Firma del cliente (rientro):</p>
      <canvas id="firmaRientroCanvas" width="700" height="180"
        style="border:2px solid #333; border-radius:4px; background:#fff; touch-action:none; max-width:100%; cursor:crosshair"></canvas>
      <br>
      <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnClearFirmaRientro">
        <i class="fas fa-eraser mr-1"></i> Cancella firma
      </button>
      @error('firma_rientro_svg')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="card-footer">
      <button class="btn btn-success btn-lg" wire:click="confermaRientro"
        wire:loading.attr="disabled"
        @if($erroreKm) disabled @endif>
        <span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i></span>
        <i class="fas fa-check mr-1"></i> Conferma rientro
      </button>
      <a href="{{ route('cortesia.index') }}" class="btn btn-secondary btn-lg ml-2">Annulla</a>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const canvas = document.getElementById('firmaRientroCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let drawing = false, lastX = 0, lastY = 0;

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    if (e.touches) return { x: (e.touches[0].clientX - rect.left) * scaleX, y: (e.touches[0].clientY - rect.top) * scaleY };
    return { x: (e.clientX - rect.left) * scaleX, y: (e.clientY - rect.top) * scaleY };
  }

  canvas.addEventListener('mousedown', e => { drawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; });
  canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; }, { passive: false });
  canvas.addEventListener('mousemove', e => {
    if (!drawing) return;
    const p = getPos(e);
    ctx.beginPath(); ctx.strokeStyle = '#000'; ctx.lineWidth = 2; ctx.lineCap = 'round';
    ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke();
    lastX = p.x; lastY = p.y;
  });
  canvas.addEventListener('touchmove', e => {
    e.preventDefault();
    if (!drawing) return;
    const p = getPos(e);
    ctx.beginPath(); ctx.strokeStyle = '#000'; ctx.lineWidth = 2; ctx.lineCap = 'round';
    ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke();
    lastX = p.x; lastY = p.y;
  }, { passive: false });
  canvas.addEventListener('mouseup', salvaFirma);
  canvas.addEventListener('touchend', salvaFirma);

  document.getElementById('btnClearFirmaRientro').addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    @this.set('firma_rientro_svg', '');
  });

  function salvaFirma() {
    drawing = false;
    const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + canvas.width + '" height="' + canvas.height + '"><image href="' + canvas.toDataURL() + '" width="' + canvas.width + '" height="' + canvas.height + '"/></svg>';
    @this.set('firma_rientro_svg', svg);
  }
});
</script>
@endpush
