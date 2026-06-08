<div>
  {{-- Barra step --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="d-flex justify-content-between">
        @foreach([1 => 'Disponibilità', 2 => 'Dati', 3 => 'Firma', 4 => 'Conferma'] as $n => $label)
        <div class="text-center {{ $step >= $n ? 'text-primary' : 'text-muted' }}" style="flex:1">
          <div style="font-size:1.5rem; font-weight:bold;">{{ $n }}</div>
          <div style="font-size:0.85rem;">{{ $label }}</div>
        </div>
        @if($n < 4)
        <div class="d-flex align-items-center text-muted"><i class="fas fa-chevron-right"></i></div>
        @endif
        @endforeach
      </div>
    </div>
  </div>

  {{-- Step 1: periodo e disponibilità --}}
  @if($step === 1)
  <div class="card">
    <div class="card-header"><h4>Seleziona il periodo</h4></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-5">
          <div class="form-group">
            <label>Data e ora consegna *</label>
            <input type="datetime-local" class="form-control @error('data_consegna') is-invalid @enderror"
              wire:model="data_consegna">
            @error('data_consegna')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group">
            <label>Data rientro prevista *</label>
            <input type="date" class="form-control @error('data_rientro_prevista') is-invalid @enderror"
              wire:model="data_rientro_prevista">
            @error('data_rientro_prevista')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-primary btn-lg" wire:click="avanti" wire:loading.attr="disabled">
        <span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i></span>
        Verifica disponibilità <i class="fas fa-arrow-right ml-1"></i>
      </button>
    </div>
  </div>
  @endif

  {{-- Step 2: selezione veicolo e dati --}}
  @if($step === 2)
  <div class="card mb-3">
    <div class="card-header"><h4>Veicoli disponibili</h4></div>
    <div class="card-body">
      @if(count($veicoliDisponibili) === 0)
        <div class="alert alert-warning">Nessun veicolo disponibile nel periodo selezionato.</div>
      @else
      <div class="row">
        @foreach($veicoliDisponibili as $v)
        <div class="col-md-4 mb-3">
          <div class="card {{ $veicolo_cortesia_id == $v['id'] ? 'border-primary' : '' }}"
            style="cursor:pointer" wire:click="selezionaVeicolo({{ $v['id'] }})">
            <div class="card-body text-center">
              <div style="font-size:2rem"><i class="fas fa-car"></i></div>
              <strong>{{ $v['targa'] }}</strong><br>
              <span class="text-muted">{{ $v['marca'] }} {{ $v['modello'] }}</span><br>
              <small>{{ ucfirst($v['carburante_tipo']) }} &bull; {{ number_format($v['km_attuali'], 0, ',', '.') }} km</small>
              @if($veicolo_cortesia_id == $v['id'])
                <div><span class="badge badge-primary mt-1">Selezionato</span></div>
              @endif
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @endif
      @error('veicolo_cortesia_id')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h4>Dati consegna</h4></div>
    <div class="card-body">
      {{-- Ricerca cliente --}}
      <div class="form-group position-relative">
        <label>Cliente *</label>
        <input type="text" class="form-control" wire:model.live.debounce.300ms="clienteSearch"
          placeholder="Cerca cliente per nome, telefono...">
        @if($clienti->count())
        <div class="list-group" style="position:absolute;z-index:1000;width:100%;max-height:200px;overflow-y:auto">
          @foreach($clienti as $c)
          <button type="button" class="list-group-item list-group-item-action py-2"
            wire:click="$set('cliente_id', {{ $c->id }}); $set('clienteSearch', '')">
            <strong>{{ $c->nome_completo }}</strong> — {{ $c->telefono }}
          </button>
          @endforeach
        </div>
        @endif
        @if($clienteSelezionato)
          <div class="mt-1 p-2 bg-light rounded">
            <i class="fas fa-user mr-1"></i>
            <strong>{{ $clienteSelezionato->nome_completo }}</strong>
            @if($clienteSelezionato->patente_numero)
              &bull; Patente: {{ $clienteSelezionato->patente_numero }}
              @if($clienteSelezionato->patente_scadenza)
                (scad. {{ $clienteSelezionato->patente_scadenza->format('d/m/Y') }})
              @endif
            @else
              <span class="text-warning small ml-1"><i class="fas fa-exclamation-triangle"></i> N. patente non inserito nel profilo cliente</span>
            @endif
          </div>
        @endif
        @error('cliente_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Km alla consegna *</label>
            <input type="number" class="form-control @error('km_consegna') is-invalid @enderror"
              wire:model="km_consegna" min="0">
            @error('km_consegna')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Livello carburante *</label>
            <div class="d-flex align-items-center">
              <input type="range" class="form-control-range" wire:model.live="carburante_consegna"
                min="0" max="100" step="5" style="flex:1">
              <span class="ml-2 badge badge-secondary" style="font-size:1rem;min-width:3rem">{{ $carburante_consegna }}%</span>
            </div>
            <div style="height:12px;background:#eee;border-radius:6px;margin-top:5px;overflow:hidden">
              <div style="height:100%;width:{{ $carburante_consegna }}%;background:{{ $carburante_consegna > 30 ? '#28a745' : '#dc3545' }};border-radius:6px;transition:width .3s"></div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Cauzione €</label>
            <input type="number" class="form-control" wire:model="cauzione_importo" min="0" step="0.01">
            <div class="form-check mt-1">
              <input type="checkbox" class="form-check-input" id="cPagata2" wire:model="cauzione_pagata">
              <label class="form-check-label" for="cPagata2">Cauzione incassata</label>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Note consegna</label>
        <textarea class="form-control" wire:model="note_consegna" rows="2"
          placeholder="Condizioni visibili del veicolo, graffi, ammaccature..."></textarea>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
      <button class="btn btn-secondary btn-lg" wire:click="indietro">
        <i class="fas fa-arrow-left mr-1"></i> Indietro
      </button>
      <button class="btn btn-primary btn-lg" wire:click="avanti" wire:loading.attr="disabled">
        <span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i></span>
        Procedi alla firma <i class="fas fa-arrow-right ml-1"></i>
      </button>
    </div>
  </div>
  @endif

  {{-- Step 3: firma --}}
  @if($step === 3)
  <div class="card">
    <div class="card-header"><h4>Firma contratto di comodato</h4></div>
    <div class="card-body">
      <div class="alert alert-info">
        <i class="fas fa-info-circle mr-1"></i>
        Il cliente <strong>{{ $clienteSelezionato?->nome_completo }}</strong>
        sta per ricevere il veicolo <strong>{{ $veicoloSelezionato?->targa }}</strong>.
        Far firmare il contratto di comodato d'uso gratuito.
      </div>

      <p class="font-weight-bold">Firma del cliente (comodatario):</p>
      <canvas id="firmaCanvas" width="700" height="200"
        style="border:2px solid #333; border-radius:4px; background:#fff; touch-action:none; max-width:100%; cursor:crosshair"></canvas>
      <br>
      <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnClearFirma">
        <i class="fas fa-eraser mr-1"></i> Cancella firma
      </button>
      @error('firma_consegna_svg')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="card-footer d-flex justify-content-between">
      <button class="btn btn-secondary btn-lg" wire:click="indietro">
        <i class="fas fa-arrow-left mr-1"></i> Indietro
      </button>
      <button class="btn btn-success btn-lg" wire:click="conferma" wire:loading.attr="disabled">
        <span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i></span>
        <i class="fas fa-check mr-1"></i> Conferma e crea prestito
      </button>
    </div>
  </div>
  @endif

  {{-- Step 4: conferma --}}
  @if($step === 4)
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
      <h3>Prestito creato con successo!</h3>
      <p class="text-muted">Il veicolo è stato registrato come consegnato.</p>
      <div class="mt-4">
        <a href="{{ route('cortesia.contratto', $prestitoId) }}" class="btn btn-primary btn-lg mr-2" target="_blank">
          <i class="fas fa-file-pdf mr-1"></i> Scarica contratto PDF
        </a>
        <a href="{{ route('cortesia.index') }}" class="btn btn-secondary btn-lg">
          <i class="fas fa-calendar mr-1"></i> Torna al calendario
        </a>
      </div>
    </div>
  </div>
  @endif
</div>

@if($step === 3)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const canvas = document.getElementById('firmaCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let drawing = false;
  let lastX = 0, lastY = 0;

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    if (e.touches) {
      return {
        x: (e.touches[0].clientX - rect.left) * scaleX,
        y: (e.touches[0].clientY - rect.top) * scaleY,
      };
    }
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

  document.getElementById('btnClearFirma').addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    @this.set('firma_consegna_svg', '');
  });

  function salvaFirma() {
    drawing = false;
    const svg = canvasToSvg(canvas, ctx);
    @this.set('firma_consegna_svg', svg);
  }

  function canvasToSvg(canvas) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' + canvas.width + '" height="' + canvas.height + '"><image href="' + canvas.toDataURL() + '" width="' + canvas.width + '" height="' + canvas.height + '"/></svg>';
  }
});
</script>
@endpush
@endif
