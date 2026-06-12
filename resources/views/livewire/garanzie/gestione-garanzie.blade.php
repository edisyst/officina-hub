<div>
  @if(session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Garanzie</h6>
    <button wire:click="apriModal()" class="btn btn-sm btn-primary">
      <i class="fas fa-plus"></i> Nuova garanzia
    </button>
  </div>

  @forelse($garanzie as $g)
  <div class="card mb-2 {{ $g->isInScadenza() ? 'border-warning' : ($g->isScaduta() ? 'border-secondary' : 'border-success') }}">
    <div class="card-body py-2 px-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <span class="badge {{ $g->badgeClass() }} mr-1">{{ $g->badgeLabel() }}</span>
          <span class="badge badge-info mr-1">{{ $g->tipo->label() }}</span>
          <strong>{{ $g->descrizione }}</strong>
          @if($g->numero_pratica)
          <small class="text-muted ml-2">Pratica: {{ $g->numero_pratica }}</small>
          @endif
          @if($g->casaMadre)
          <small class="text-muted ml-2"><i class="fas fa-building"></i> {{ $g->casaMadre->ragione_sociale }}</small>
          @endif
          <br>
          <small class="text-muted">
            Dal {{ $g->data_inizio->format('d/m/Y') }}
            @if($g->data_fine) al {{ $g->data_fine->format('d/m/Y') }} @endif
            @if($g->km_inizio) · KM: {{ number_format($g->km_inizio) }}@if($g->km_fine) – {{ number_format($g->km_fine) }}@endif @endif
          </small>
          @if($g->isInScadenza())
          <span class="badge badge-warning ml-2">
            <i class="fas fa-exclamation-triangle"></i> Scade tra {{ $g->data_fine->diffInDays(now()) }} giorni
          </span>
          @endif
        </div>
        <div class="d-flex" style="gap:.25rem">
          <button wire:click="apriModal({{ $g->id }})" class="btn btn-xs btn-info">
            <i class="fas fa-edit"></i>
          </button>
          <button wire:click="elimina({{ $g->id }})"
            wire:confirm="Eliminare questa garanzia?"
            class="btn btn-xs btn-danger">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
  @empty
  <p class="text-muted text-center py-3">Nessuna garanzia registrata per questo veicolo.</p>
  @endforelse

  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica Garanzia' : 'Nuova Garanzia' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tipo *</label>
                <select wire:model="tipo" class="form-control @error('tipo') is-invalid @enderror">
                  @foreach($tipiGaranzia as $t)
                  <option value="{{ $t->value }}">{{ $t->label() }}</option>
                  @endforeach
                </select>
                @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Casa Madre</label>
                <select wire:model="casa_madre_id" class="form-control">
                  <option value="">— Nessuna —</option>
                  @foreach($caseMadri as $cm)
                  <option value="{{ $cm->id }}">{{ $cm->ragione_sociale }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Descrizione *</label>
            <input wire:model="descrizione" type="text"
              class="form-control @error('descrizione') is-invalid @enderror"
              placeholder="Es. Garanzia ufficiale 5 anni / 100.000 km">
            @error('descrizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Data inizio *</label>
                <input wire:model="data_inizio" type="date"
                  class="form-control @error('data_inizio') is-invalid @enderror">
                @error('data_inizio')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Data fine</label>
                <input wire:model="data_fine" type="date"
                  class="form-control @error('data_fine') is-invalid @enderror">
                @error('data_fine')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>KM inizio</label>
                <input wire:model="km_inizio" type="number" min="0" class="form-control">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>KM fine</label>
                <input wire:model="km_fine" type="number" min="0" class="form-control">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>N° Pratica casa madre</label>
                <input wire:model="numero_pratica" type="text" class="form-control"
                  placeholder="Es. WRN-2024-00123">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="d-block">Stato</label>
                <div class="custom-control custom-switch mt-2">
                  <input type="checkbox" class="custom-control-input" id="attiva_toggle"
                    wire:model="attiva">
                  <label class="custom-control-label" for="attiva_toggle">Garanzia attiva</label>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Annulla</button>
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
