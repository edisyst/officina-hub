<div>
  <div class="row">

    {{-- Colonna sinistra: lista template --}}
    <div class="col-md-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-list-alt mr-1"></i> Template</span>
          <button wire:click="aprirTemplateModal()" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div class="card-body p-0">
          @forelse($templates as $t)
            <div class="d-flex align-items-center px-3 py-2 border-bottom
                        {{ $templateSelezionatoId == $t->id ? 'bg-light' : '' }}">
              <button wire:click="selezionaTemplate({{ $t->id }})"
                      class="btn btn-link text-left flex-grow-1 text-dark p-0">
                <i class="fas fa-{{ $t->attivo ? 'check-circle text-success' : 'circle text-muted' }} mr-1"></i>
                {{ $t->nome }}
              </button>
              <div class="btn-group btn-group-sm ml-2">
                <button wire:click="aprirTemplateModal({{ $t->id }})" class="btn btn-outline-secondary">
                  <i class="fas fa-edit"></i>
                </button>
                <button wire:click="toggleAttivo({{ $t->id }})" class="btn btn-outline-secondary">
                  <i class="fas fa-power-off"></i>
                </button>
                <button wire:click="eliminaTemplate({{ $t->id }})"
                        wire:confirm="Eliminare il template «{{ $t->nome }}»? Verranno eliminate anche tutte le voci."
                        class="btn btn-outline-danger">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          @empty
            <p class="text-muted text-center py-3 mb-0">Nessun template</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Colonna destra: voci del template selezionato --}}
    <div class="col-md-8">
      @if($templateSelezionatoId)
        @php $template = $templates->firstWhere('id', $templateSelezionatoId) @endphp
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-tasks mr-1"></i> Voci — {{ $template->nome }}</span>
            <button wire:click="apriVoceModal()" class="btn btn-sm btn-success">
              <i class="fas fa-plus"></i> Aggiungi voce
            </button>
          </div>
          <div class="card-body p-0">
            @if($voci->isEmpty())
              <p class="text-muted text-center py-3 mb-0">Nessuna voce. Aggiungine una.</p>
            @else
              <table class="table table-sm mb-0"
                     x-data
                     x-init="
                       Sortable.create($el.querySelector('tbody'), {
                         animation: 150,
                         handle: '.drag-handle',
                         onEnd(evt) {
                           const rows = [...$el.querySelectorAll('tbody tr')];
                           const ordini = rows.map((r, i) => ({ value: parseInt(r.dataset.id), order: i }));
                           $wire.riordinaVoci(ordini);
                         }
                       });
                     ">
                <thead>
                  <tr>
                    <th style="width:30px"></th>
                    <th>Etichetta</th>
                    <th>Tipo</th>
                    <th style="width:80px">Obblig.</th>
                    <th style="width:90px"></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($voci as $v)
                    <tr data-id="{{ $v->id }}">
                      <td><i class="fas fa-grip-vertical text-muted drag-handle" style="cursor:grab"></i></td>
                      <td>
                        {{ $v->etichetta }}
                        @if($v->unita_misura)<small class="text-muted">({{ $v->unita_misura }})</small>@endif
                      </td>
                      <td>
                        <span class="badge badge-secondary">{{ $v->tipo }}</span>
                      </td>
                      <td class="text-center">
                        @if($v->obbligatoria)
                          <i class="fas fa-check text-danger"></i>
                        @endif
                      </td>
                      <td class="text-right">
                        <button wire:click="apriVoceModal({{ $v->id }})" class="btn btn-xs btn-outline-secondary">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button wire:click="eliminaVoce({{ $v->id }})"
                                wire:confirm="Eliminare la voce «{{ $v->etichetta }}»?"
                                class="btn btn-xs btn-outline-danger">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @endif
          </div>
        </div>
      @else
        <div class="text-muted text-center py-5">
          <i class="fas fa-arrow-left fa-2x mb-2"></i><br>
          Seleziona un template per gestire le voci
        </div>
      @endif
    </div>
  </div>

  {{-- Modal template --}}
  @if($showTemplateModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <form wire:submit="salvaTemplate" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $templateEditId ? 'Modifica' : 'Nuovo' }} template</h5>
          <button type="button" class="close" wire:click="$set('showTemplateModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nome *</label>
            <input type="text" wire:model="templateNome" class="form-control" required>
            @error('templateNome')<small class="text-danger">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label>Descrizione</label>
            <textarea wire:model="templateDesc" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" wire:model="templateAttivo" class="custom-control-input" id="tplAttivo">
              <label class="custom-control-label" for="tplAttivo">Attivo</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showTemplateModal',false)">Annulla</button>
          <button type="submit" class="btn btn-primary">Salva</button>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Modal voce --}}
  @if($showVoceModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <form wire:submit="salvaVoce" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $voceEditId ? 'Modifica' : 'Nuova' }} voce</h5>
          <button type="button" class="close" wire:click="$set('showVoceModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Etichetta *</label>
            <input type="text" wire:model="voceEtichetta" class="form-control" required>
            @error('voceEtichetta')<small class="text-danger">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label>Tipo *</label>
            <select wire:model.live="voceTipo" class="custom-select">
              <option value="si_no">Sì / No</option>
              <option value="numerico">Numerico</option>
              <option value="testo_libero">Testo libero</option>
              <option value="foto_obbligatoria">Foto obbligatoria</option>
            </select>
          </div>
          @if($voceTipo === 'numerico')
          <div class="form-group">
            <label>Unità di misura</label>
            <input type="text" wire:model="voceUnita" class="form-control" placeholder="es. km, bar, mm">
          </div>
          @endif
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" wire:model="voceObblig" class="custom-control-input" id="voceObblig">
              <label class="custom-control-label" for="voceObblig">Obbligatoria</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showVoceModal',false)">Annulla</button>
          <button type="submit" class="btn btn-primary">Salva</button>
        </div>
      </form>
    </div>
  </div>
  @endif
</div>
