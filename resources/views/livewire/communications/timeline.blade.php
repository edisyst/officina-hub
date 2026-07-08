<div>
  {{-- Toolbar --}}
  <div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <div class="input-group input-group-sm" style="max-width:300px">
      <input type="text" class="form-control" placeholder="Cerca nel testo…"
             wire:model.live.debounce.400ms="search">
      <div class="input-group-append">
        <span class="input-group-text"><i class="fas fa-search"></i></span>
      </div>
    </div>

    <select wire:model.live="filterChannel" class="form-control form-control-sm" style="max-width:160px">
      <option value="">Tutti i canali</option>
      <option value="phone">Telefono</option>
      <option value="note">Note</option>
      <option value="email">Email</option>
      <option value="whatsapp">WhatsApp</option>
      <option value="sms">SMS</option>
    </select>

    <button wire:click="apriAnnota" class="btn btn-sm btn-outline-primary ml-auto">
      <i class="fas fa-plus mr-1"></i> Annota
    </button>
  </div>

  {{-- Timeline --}}
  <div class="timeline timeline-inverse">
    @forelse ($communications as $comm)
    @php $date = $comm->occurred_at->format('d/m/Y'); @endphp

    <div class="time-label">
      <span class="bg-secondary">{{ $date }}</span>
    </div>

    <div x-data="{ expanded: false }">
      <i class="{{ $comm->channelIcon() }}"></i>
      <div class="timeline-item">
        <span class="time">
          <i class="fas fa-clock mr-1"></i>
          <span title="{{ $comm->occurred_at->format('d/m/Y H:i') }}">
            {{ $comm->occurred_at->diffForHumans() }}
          </span>
          &nbsp;—&nbsp;
          <span class="badge {{ $comm->direction === 'outbound' ? 'badge-primary' : 'badge-success' }}">
            <i class="fas fa-arrow-{{ $comm->direction === 'outbound' ? 'up' : 'down' }} mr-1"></i>
            {{ $comm->direction === 'outbound' ? 'Uscita' : 'Entrata' }}
          </span>
        </span>
        <h3 class="timeline-header">
          {{ $comm->channelLabel() }}
          @if($comm->subject)
          — <em>{{ $comm->subject }}</em>
          @endif
          <small class="text-muted ml-2">
            {{ $comm->user?->name ?? 'Sistema' }}
          </small>
          @if($comm->workOrder && ! $workOrderId)
          <small class="text-muted ml-1">(OdL #{{ $comm->workOrder->numero }})</small>
          @endif
        </h3>
        <div class="timeline-body">
          <span x-show="!expanded" @click="expanded=true" style="cursor:pointer">
            {{ Str::limit($comm->body, 200) }}
            @if(strlen($comm->body) > 200)
            <a class="text-primary" href="#">mostra tutto</a>
            @endif
          </span>
          <span x-show="expanded" @click="expanded=false" style="cursor:pointer">
            {{ $comm->body }}
            <a class="text-primary" href="#">riduci</a>
          </span>
        </div>
      </div>
    </div>
    @empty
    <li>
      <div class="timeline-item">
        <div class="timeline-body text-muted">Nessuna comunicazione registrata.</div>
      </div>
    </li>
    @endforelse

    <div><i class="fas fa-clock bg-gray"></i></div>
  </div>

  {{ $communications->links() }}

  {{-- Modale annotazione --}}
  @if($showAnnotaModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Nuova annotazione</h5>
          <button type="button" class="close" wire:click="$set('showAnnotaModal',false)">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="col-form-label-sm">Canale</label>
                <select wire:model="annoChannel" class="form-control form-control-sm @error('annoChannel') is-invalid @enderror">
                  <option value="phone">Telefono</option>
                  <option value="note">Nota interna</option>
                  <option value="email">Email</option>
                  <option value="whatsapp">WhatsApp</option>
                  <option value="sms">SMS</option>
                </select>
                @error('annoChannel') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="col-form-label-sm">Direzione</label>
                <select wire:model="annoDirection" class="form-control form-control-sm @error('annoDirection') is-invalid @enderror">
                  <option value="inbound">Entrata (cliente chiama)</option>
                  <option value="outbound">Uscita (noi chiamiamo)</option>
                </select>
                @error('annoDirection') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="col-form-label-sm">Data / ora</label>
                <input type="datetime-local" wire:model="annoOccurredAt"
                       class="form-control form-control-sm @error('annoOccurredAt') is-invalid @enderror">
                @error('annoOccurredAt') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-form-label-sm">Oggetto <small class="text-muted">(opzionale)</small></label>
            <input type="text" wire:model="annoSubject" class="form-control form-control-sm"
                   placeholder="Breve oggetto…">
          </div>

          <div class="form-group">
            <label class="col-form-label-sm">Testo <span class="text-danger">*</span></label>
            <textarea wire:model="annoBody" rows="4"
                      class="form-control form-control-sm @error('annoBody') is-invalid @enderror"
                      placeholder="Contenuto della comunicazione…"></textarea>
            @error('annoBody') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showAnnotaModal',false)">
            Annulla
          </button>
          <button type="button" class="btn btn-primary btn-sm" wire:click="salvaAnnotazione"
                  wire:loading.attr="disabled">
            <span wire:loading wire:target="salvaAnnotazione"><i class="fas fa-spinner fa-spin mr-1"></i></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
