<div>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Veicoli</h3>
      <div class="card-tools d-flex gap-2">
        <input wire:model.live.debounce.300ms="search" type="text"
          class="form-control form-control-sm mr-2" placeholder="Cerca per targa, VIN, marca, modello...">
        @can('create', \App\Models\Veicolo::class)
        <button wire:click="apriModal()" class="btn btn-sm btn-primary">
          <i class="fas fa-plus"></i> Nuovo Veicolo
        </button>
        @endcan
      </div>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Tipo</th><th>Targa</th><th>Marca/Modello</th><th>Anno</th><th>Alimentazione</th><th>Commesse</th><th width="100">Azioni</th>
          </tr>
        </thead>
        <tbody>
          @forelse($veicoli as $v)
          <tr>
            <td><i class="fas fa-{{ $v->tipo->value === 'moto' ? 'motorcycle' : 'car' }}"></i> {{ $v->tipo->label() }}</td>
            <td><a href="{{ route('veicoli.show', $v->id) }}"><strong>{{ $v->targa ?? '-' }}</strong></a></td>
            <td>{{ $v->marca }} {{ $v->modello }} {{ $v->versione }}</td>
            <td>{{ $v->anno_immatricolazione ?? '-' }}</td>
            <td>{{ $v->alimentazione->label() }}</td>
            <td>{{ $v->commesse_count }}</td>
            <td>
              @can('update', $v)
              <button wire:click="apriModal({{ $v->id }})" class="btn btn-xs btn-info">
                <i class="fas fa-edit"></i>
              </button>
              @endcan
              @can('delete', $v)
              <button wire:click="elimina({{ $v->id }})"
                wire:confirm="Eliminare il veicolo '{{ $v->targa }}'?"
                class="btn btn-xs btn-danger">
                <i class="fas fa-trash"></i>
              </button>
              @endcan
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center text-muted py-3">Nessun veicolo trovato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $veicoli->links() }}</div>
  </div>

  <!-- Modal Crea/Modifica Veicolo -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica Veicolo' : 'Nuovo Veicolo' }}</h5>
          <button type="button" class="close" wire:click="chiudiModal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Tipo *</label>
                <select wire:model="tipo" class="form-control @error('tipo') is-invalid @enderror">
                  @foreach($tipiVeicolo as $t)
                  <option value="{{ $t->value }}">{{ $t->label() }}</option>
                  @endforeach
                </select>
                @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Targa</label>
                <div class="input-group">
                  <input wire:model="targa" type="text" class="form-control" style="text-transform:uppercase"
                    @if(app(\App\Services\LookupTarga\LookupTargaService::class)->isAbilitato() && app(\App\Services\LookupTarga\LookupTargaService::class)->isAutoSearch())
                      wire:blur="cercaTarga"
                    @endif>
                  @if(app(\App\Services\LookupTarga\LookupTargaService::class)->isAbilitato())
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary" wire:click="cercaTarga"
                      wire:loading.attr="disabled" wire:target="cercaTarga" title="Cerca dati dal DB targa">
                      <span wire:loading wire:target="cercaTarga"><i class="fas fa-spinner fa-spin"></i></span>
                      <span wire:loading.remove wire:target="cercaTarga"><i class="fas fa-search"></i></span>
                    </button>
                  </div>
                  @endif
                </div>
                @if($lookupMessaggio)
                  <small class="text-{{ str_contains($lookupMessaggio, 'non trovata') || str_contains($lookupMessaggio, 'non disponibile') ? 'warning' : 'info' }}">
                    <i class="fas fa-info-circle mr-1"></i>{{ $lookupMessaggio }}
                  </small>
                @endif
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>VIN / Telaio</label>
                <input wire:model="vin" type="text" class="form-control" style="text-transform:uppercase">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Marca *</label>
                <input wire:model="marca" type="text" class="form-control @error('marca') is-invalid @enderror">
                @error('marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Modello *</label>
                <input wire:model="modello" type="text" class="form-control @error('modello') is-invalid @enderror">
                @error('modello')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Versione / Allestimento</label>
                <input wire:model="versione" type="text" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Alimentazione *</label>
                <select wire:model="alimentazione" class="form-control">
                  @foreach($alimentazioni as $a)
                  <option value="{{ $a->value }}">{{ $a->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Cilindrata (cc)</label>
                <input wire:model="cilindrata" type="number" class="form-control" min="0">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Anno Immatricolazione</label>
                <input wire:model="anno_immatricolazione" type="number" class="form-control" min="1900" max="{{ date('Y') }}">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Colore</label>
                <input wire:model="colore" type="text" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>KM Attuali</label>
                <input wire:model="km_attuali" type="number" class="form-control" min="0">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="chiudiModal">Annulla</button>
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
