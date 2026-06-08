<div>
  {{-- Lista set pneumatici --}}
  <div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Set pneumatici registrati</strong>
    <button wire:click="apriModal()" class="btn btn-sm btn-success">
      <i class="fas fa-plus"></i> Aggiungi set
    </button>
  </div>

  @if($pneumatici->isEmpty())
    <p class="text-muted text-center py-3">Nessun set registrato per questo veicolo.</p>
  @else
  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
      <thead class="thead-light">
        <tr>
          <th>Stagione</th>
          <th>Marca / Misura</th>
          <th>Stato</th>
          <th>Posizione</th>
          <th>Etichetta</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($pneumatici as $p)
        <tr>
          <td>
            <span class="badge {{ $p->stagione->badgeClass() }}">{{ $p->stagione->label() }}</span>
          </td>
          <td>
            <strong>{{ $p->marca }}</strong>{{ $p->modello ? ' ' . $p->modello : '' }}<br>
            <small class="text-muted">{{ $p->misura }}{{ $p->dotati_di_cerchi ? ' + cerchi' : '' }}</small>
          </td>
          <td>
            <span class="badge {{ $p->stato->badgeClass() }}">{{ $p->stato->label() }}</span>
          </td>
          <td>
            @php $ult = $p->movimenti->first(); @endphp
            @if($ult && $ult->ubicazione)
              <small>{{ $ult->ubicazione }}</small>
            @else
              <small class="text-muted">—</small>
            @endif
          </td>
          <td>
            @if($p->stato->value === 'in_deposito')
              <a href="{{ route('deposito.etichetta', $p->id) }}" target="_blank"
                 class="btn btn-xs btn-outline-secondary" title="Stampa etichetta">
                <i class="fas fa-tag"></i>
              </a>
            @endif
          </td>
          <td class="text-right">
            <button wire:click="apriModal({{ $p->id }})" class="btn btn-xs btn-outline-primary">
              <i class="fas fa-edit"></i>
            </button>
            @if(!in_array($p->stato->value, ['smaltito']))
            <button wire:click="avviaSmaltimento({{ $p->id }})" class="btn btn-xs btn-outline-danger"
                    title="Smaltisci">
              <i class="fas fa-trash-alt"></i>
            </button>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif

  {{-- Modal aggiungi/modifica --}}
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica set' : 'Aggiungi set pneumatici' }}</h5>
          <button wire:click="chiudiModal" class="close"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Stagione *</label>
                <select wire:model="stagione" class="form-control @error('stagione') is-invalid @enderror">
                  @foreach($stagioni as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                  @endforeach
                </select>
                @error('stagione')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Stato *</label>
                <select wire:model="stato" class="form-control @error('stato') is-invalid @enderror">
                  @foreach($statiPneumatico as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                  @endforeach
                </select>
                @error('stato')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Marca *</label>
                <input wire:model="marca" type="text" class="form-control @error('marca') is-invalid @enderror"
                       placeholder="es. Michelin">
                @error('marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Modello</label>
                <input wire:model="modello" type="text" class="form-control" placeholder="es. Pilot Sport 4">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label>Misura *</label>
                <input wire:model="misura" type="text" class="form-control @error('misura') is-invalid @enderror"
                       placeholder="es. 205/55 R16 91H">
                @error('misura')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Larghezza</label>
                <input wire:model="larghezza" type="number" class="form-control" placeholder="205">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Rapporto</label>
                <input wire:model="rapporto" type="number" class="form-control" placeholder="55">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Diametro</label>
                <input wire:model="diametro" type="number" class="form-control" placeholder="16">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>N. pezzi</label>
                <input wire:model="numero_pezzi" type="number" min="1" max="8" class="form-control">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Indice carico</label>
                <input wire:model="indice_carico" type="text" class="form-control" placeholder="91">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Indice velocità</label>
                <input wire:model="indice_velocita" type="text" class="form-control" placeholder="H">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Anno produzione</label>
                <input wire:model="anno_produzione" type="number" class="form-control" placeholder="2022">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mt-4">
                <div class="form-check">
                  <input wire:model="dotati_di_cerchi" type="checkbox" class="form-check-input" id="cerchi">
                  <label class="form-check-label" for="cerchi">Con cerchi</label>
                </div>
              </div>
            </div>
          </div>

          @if($dotati_di_cerchi)
          <div class="form-group">
            <label>Tipo cerchi</label>
            <input wire:model="tipo_cerchi" type="text" class="form-control" placeholder="es. acciaio, lega">
          </div>
          @endif

          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button wire:click="chiudiModal" class="btn btn-secondary">Annulla</button>
          <button wire:click="salva" class="btn btn-primary">
            <i class="fas fa-save"></i> Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal conferma smaltimento --}}
  @if($showConfermaSmaltimento)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Conferma smaltimento</h5>
        </div>
        <div class="modal-body">
          <p>Confermi lo smaltimento di questo set? L'operazione non può essere annullata.</p>
          <p class="text-muted small">Il set sarà marcato come smaltito. Registra il documento PFU separatamente.</p>
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showConfermaSmaltimento', false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="confermaSmaltimento" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Conferma smaltimento
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
