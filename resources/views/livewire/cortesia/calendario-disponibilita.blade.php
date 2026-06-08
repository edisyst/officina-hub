<div>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Disponibilità Veicoli di Cortesia</h3>
      @hasanyrole('admin|accettatore')
      <div class="card-tools">
        <button class="btn btn-primary btn-sm" wire:click="apriModalNuovo">
          <i class="fas fa-plus mr-1"></i> Nuova prenotazione
        </button>
      </div>
      @endhasanyrole
    </div>
    <div class="card-body p-0">
      <div id="calendario-cortesia" style="min-height:500px; padding:10px"></div>
    </div>
  </div>

  {{-- Modal prenotazione --}}
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $prestitoId ? 'Modifica prenotazione' : 'Nuova prenotazione' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)">&times;</button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Veicolo *</label>
                <select class="form-control @error('veicolo_cortesia_id') is-invalid @enderror"
                  wire:model="veicolo_cortesia_id">
                  <option value="">— Seleziona —</option>
                  @foreach($veicoli as $v)
                  <option value="{{ $v->id }}">{{ $v->targa }} — {{ $v->marca }} {{ $v->modello }}</option>
                  @endforeach
                </select>
                @error('veicolo_cortesia_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Stato *</label>
                <select class="form-control" wire:model="stato">
                  @foreach($stati as $s)
                  <option value="{{ $s->value }}">{{ $s->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          {{-- Ricerca cliente --}}
          <div class="form-group">
            <label>Cliente *</label>
            <input type="text" class="form-control" wire:model.live.debounce.300ms="clienteSearch"
              placeholder="Cerca cliente...">
            @if($clienti->count())
            <div class="list-group mt-1" style="max-height:150px;overflow-y:auto;position:absolute;z-index:1000;width:calc(100% - 30px)">
              @foreach($clienti as $c)
              <button type="button" class="list-group-item list-group-item-action py-1"
                wire:click="$set('cliente_id', {{ $c->id }}); $set('clienteSearch', '{{ addslashes($c->nome_completo) }}')">
                {{ $c->nome_completo }} — {{ $c->telefono }}
              </button>
              @endforeach
            </div>
            @endif
            @if($cliente_id && !$clienteSearch)
              <small class="text-muted">Cliente ID: {{ $cliente_id }}</small>
            @endif
            @error('cliente_id')<div class="text-danger small">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Data consegna *</label>
                <input type="datetime-local" class="form-control @error('data_consegna') is-invalid @enderror"
                  wire:model="data_consegna">
                @error('data_consegna')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Data rientro prevista *</label>
                <input type="date" class="form-control @error('data_rientro_prevista') is-invalid @enderror"
                  wire:model="data_rientro_prevista">
                @error('data_rientro_prevista')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Km consegna</label>
                <input type="number" class="form-control" wire:model="km_consegna" min="0">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Carburante consegna (%)</label>
                <input type="range" class="form-control-range" wire:model="carburante_consegna" min="0" max="100" step="5">
                <small class="text-muted">{{ $carburante_consegna }}%</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Cauzione €</label>
                <input type="number" class="form-control" wire:model="cauzione_importo" min="0" step="0.01">
                <div class="form-check mt-1">
                  <input type="checkbox" class="form-check-input" id="cPagata" wire:model="cauzione_pagata">
                  <label class="form-check-label" for="cPagata">Cauzione pagata</label>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Note</label>
            <textarea class="form-control" wire:model="note_consegna" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          @if($prestitoId)
          <button type="button" class="btn btn-danger mr-auto" wire:click="elimina"
            onclick="return confirm('Eliminare la prenotazione?')">Elimina</button>
          @endif
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salva" wire:loading.attr="disabled">
            <span wire:loading wire:target="salva"><i class="fas fa-spinner fa-spin mr-1"></i></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>

@push('scripts')
<script>
document.addEventListener('livewire:navigated', initCalendarioCortesia);
document.addEventListener('DOMContentLoaded', initCalendarioCortesia);

function initCalendarioCortesia() {
  const el = document.getElementById('calendario-cortesia');
  if (!el || el._fcInitialized) return;
  el._fcInitialized = true;

  const calendar = new FullCalendar.Calendar(el, {
    schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
    initialView: 'resourceTimelineWeek',
    locale: 'it',
    height: 'auto',
    resourceAreaHeaderContent: 'Veicolo',
    resources: function(fetchInfo, successCb, failureCb) {
      fetch('/api/cortesia/disponibilita?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr)
        .then(r => r.json())
        .then(d => successCb(d.risorse))
        .catch(failureCb);
    },
    events: function(fetchInfo, successCb, failureCb) {
      fetch('/api/cortesia/disponibilita?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr)
        .then(r => r.json())
        .then(d => successCb(d.eventi))
        .catch(failureCb);
    },
    selectable: true,
    select: function(info) {
      @this.apriModalNuovo(info.startStr, info.resource ? info.resource.id : '');
    },
    eventClick: function(info) {
      const prestitoId = info.event.extendedProps.prestito_id;
      if (prestitoId) {
        @this.apriModalModifica(prestitoId);
      }
    },
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'resourceTimelineDay,resourceTimelineWeek,resourceTimelineMonth',
    },
  });

  calendar.render();

  window.addEventListener('calendar-refresh', () => calendar.refetchEvents());
  document.addEventListener('livewire:updated', () => window.dispatchEvent(new Event('calendar-refresh')));
}
</script>
@endpush
