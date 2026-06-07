<div>
  <!-- Filtri -->
  <div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
      <div class="d-flex align-items-center flex-wrap gap-2"
        x-data="{
          filtroRisorse: 'tutti',
          setFiltro(tipo) {
            this.filtroRisorse = tipo;
            if (!window._calendario) return;
            if (tipo === 'tutti') {
              window._calendario.setOption('resources', '/api/risorse-agenda');
            } else {
              window._calendario.setOption('resources', '/api/risorse-agenda?tipo=' + tipo);
            }
          }
        }">
        <span class="mr-2 font-weight-bold">Mostra:</span>
        <div class="btn-group btn-group-sm">
          <button class="btn" :class="filtroRisorse === 'tutti' ? 'btn-primary' : 'btn-outline-primary'" @click="setFiltro('tutti')">Tutti</button>
          <button class="btn" :class="filtroRisorse === 'ponte' ? 'btn-success' : 'btn-outline-success'" @click="setFiltro('ponte')">Solo Ponti</button>
          <button class="btn" :class="filtroRisorse === 'meccanico' ? 'btn-info' : 'btn-outline-info'" @click="setFiltro('meccanico')">Solo Meccanici</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Calendario FullCalendar -->
  <div class="card">
    <div class="card-body"
      x-data="{
        calendar: null,
        init() {
          const { Calendar, plugins, itLocale } = window.FullCalendar;
          this.calendar = new Calendar(this.$refs.calendarEl, {
            plugins: plugins,
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
            locale: itLocale,
            headerToolbar: {
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,resourceTimeGridDay,resourceTimelineWeek'
            },
            initialView: 'timeGridWeek',
            height: 'auto',
            events: {
              url: '/api/appuntamenti',
              failure() { alert('Errore nel caricamento degli appuntamenti.'); }
            },
            resources: {
              url: '/api/risorse-agenda',
              failure() { alert('Errore nel caricamento delle risorse.'); }
            },
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            select: (info) => {
              @this.apriModalNuovo(info.startStr, info.endStr, info.resource ? info.resource.id : null);
            },
            eventClick: (info) => {
              @this.apriModalModifica(parseInt(info.event.id));
            },
            eventDrop: (info) => {
              @this.sposta(parseInt(info.event.id), info.event.startStr, info.event.endStr);
            },
            eventResize: (info) => {
              @this.ridimensiona(parseInt(info.event.id), info.event.startStr, info.event.endStr);
            },
          });
          this.calendar.render();
          window._calendario = this.calendar;

          window.addEventListener('calendar-refresh', () => {
            this.calendar.refetchEvents();
          });
        }
      }"
    >
      <div x-ref="calendarEl" wire:ignore></div>
    </div>
  </div>

  <!-- Modal Appuntamento -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-calendar-alt mr-2"></i>
            {{ $appuntamentoId ? 'Modifica Appuntamento' : 'Nuovo Appuntamento' }}
          </h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label>Titolo <span class="text-danger">*</span></label>
                <input type="text" wire:model="titolo" class="form-control @error('titolo') is-invalid @enderror" placeholder="Oggetto dell'appuntamento">
                @error('titolo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Data/Ora Inizio <span class="text-danger">*</span></label>
                <input type="datetime-local" wire:model="data_ora_inizio" class="form-control @error('data_ora_inizio') is-invalid @enderror">
                @error('data_ora_inizio')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Data/Ora Fine <span class="text-danger">*</span></label>
                <input type="datetime-local" wire:model="data_ora_fine" class="form-control @error('data_ora_fine') is-invalid @enderror">
                @error('data_ora_fine')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Stato</label>
                <select wire:model="stato" class="form-control">
                  @foreach($stati as $s)
                  <option value="{{ $s->value }}">{{ $s->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Ponte</label>
                <select wire:model="ponte_id" class="form-control">
                  <option value="">— nessuno —</option>
                  @foreach($ponti as $ponte)
                  <option value="{{ $ponte->id }}">{{ $ponte->nome }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Meccanico</label>
                <select wire:model="user_id" class="form-control">
                  <option value="">— nessuno —</option>
                  @foreach($meccanici as $mec)
                  <option value="{{ $mec->id }}">{{ $mec->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label>Collega a Commessa</label>
                <div class="input-group">
                  <input type="text" wire:model.live.debounce.400ms="commessaSearch"
                    class="form-control" placeholder="Cerca per numero, cliente, targa...">
                </div>
                @if($commesse->count())
                <div class="list-group mt-1 shadow-sm" style="position:absolute;z-index:1050;max-width:500px">
                  @foreach($commesse as $c)
                  <button type="button"
                    wire:click="$set('commessa_id', {{ $c->id }}); $set('commessaSearch', '{{ addslashes($c->numero) }}')"
                    class="list-group-item list-group-item-action py-1">
                    <strong>{{ $c->numero }}</strong>
                    — {{ $c->cliente->nome_completo ?? '?' }}
                    @if($c->veicolo?->targa) ({{ $c->veicolo->targa }}) @endif
                  </button>
                  @endforeach
                </div>
                @endif
                @if($commessa_id)
                <small class="text-success mt-1 d-block"><i class="fas fa-link mr-1"></i>Commessa collegata: {{ $commessa_id }}</small>
                @endif
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-check">
            <input type="checkbox" wire:model="tutto_il_giorno" class="form-check-input" id="tuttoGiorno">
            <label class="form-check-label" for="tuttoGiorno">Tutto il giorno</label>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <div>
            @if($appuntamentoId)
            <button type="button" class="btn btn-outline-danger btn-sm"
              wire:click="elimina"
              wire:confirm="Sei sicuro di voler eliminare questo appuntamento?">
              <i class="fas fa-trash"></i> Elimina
            </button>
            @endif
          </div>
          <div>
            <button type="button" class="btn btn-secondary mr-2" wire:click="$set('showModal', false)">Annulla</button>
            <button type="button" class="btn btn-primary" wire:click="salva" wire:loading.attr="disabled">
              <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
              Salva
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>

@push('styles')
<style>
  .fc { font-size: 0.875rem; }
  .fc .fc-toolbar-title { font-size: 1.2rem; }
</style>
@endpush
