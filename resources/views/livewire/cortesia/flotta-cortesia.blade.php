<div>
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-car mr-2"></i>Flotta Veicoli di Cortesia</h3>
      @can('create', \App\Models\VeicoloCortesia::class)
      <div class="card-tools">
        <button class="btn btn-primary btn-sm" wire:click="apriModalNuovo">
          <i class="fas fa-plus mr-1"></i> Nuovo veicolo
        </button>
      </div>
      @endcan
    </div>
    <div class="card-body p-0">
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th>Targa</th>
            <th>Marca / Modello</th>
            <th>Tipo</th>
            <th>Carburante</th>
            <th>Km attuali</th>
            <th>Km in prestito</th>
            <th>Prestiti</th>
            <th>Stato</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($veicoli as $v)
          <tr>
            <td><strong>{{ $v->targa }}</strong></td>
            <td>{{ $v->marca }} {{ $v->modello }} {{ $v->anno ? "({$v->anno})" : '' }}</td>
            <td><i class="{{ $v->iconaTipo() }}"></i> {{ ucfirst($v->tipo) }}</td>
            <td>{{ ucfirst($v->carburante_tipo) }}</td>
            <td>{{ number_format($v->km_attuali, 0, ',', '.') }}</td>
            <td>{{ number_format($v->km_totali_prestito, 0, ',', '.') }}</td>
            <td>
              <span class="badge badge-secondary">{{ $v->prestiti_count }}</span>
              @if($v->prestiti_attivi_count > 0)
                <span class="badge badge-primary">{{ $v->prestiti_attivi_count }} attivi</span>
              @endif
            </td>
            <td>
              @if($v->prestiti_attivi_count > 0)
                <span class="badge badge-primary">In prestito</span>
              @elseif($v->attivo)
                <span class="badge badge-success">Disponibile</span>
              @else
                <span class="badge badge-secondary">Inattivo</span>
              @endif
            </td>
            <td class="text-right">
              @can('update', $v)
              <button class="btn btn-xs btn-default" wire:click="apriModalModifica({{ $v->id }})" title="Modifica">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-xs btn-{{ $v->attivo ? 'warning' : 'success' }}" wire:click="toggleAttivo({{ $v->id }})" title="{{ $v->attivo ? 'Disattiva' : 'Attiva' }}">
                <i class="fas fa-{{ $v->attivo ? 'pause' : 'play' }}"></i>
              </button>
              @endcan
              @can('delete', $v)
              <button class="btn btn-xs btn-danger" wire:click="elimina({{ $v->id }})"
                onclick="return confirm('Eliminare questo veicolo?')" title="Elimina">
                <i class="fas fa-trash"></i>
              </button>
              @endcan
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center text-muted py-3">Nessun veicolo di cortesia registrato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Modal nuovo/modifica veicolo --}}
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $veicoloId ? 'Modifica veicolo' : 'Nuovo veicolo di cortesia' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)">&times;</button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Targa *</label>
                <input type="text" class="form-control @error('targa') is-invalid @enderror"
                  wire:model="targa" placeholder="AB123CD" style="text-transform:uppercase">
                @error('targa')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Tipo *</label>
                <select class="form-control" wire:model="tipo">
                  <option value="auto">Auto</option>
                  <option value="moto">Moto</option>
                  <option value="furgone">Furgone</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Marca *</label>
                <input type="text" class="form-control @error('marca') is-invalid @enderror" wire:model="marca">
                @error('marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Modello *</label>
                <input type="text" class="form-control @error('modello') is-invalid @enderror" wire:model="modello">
                @error('modello')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Anno</label>
                <input type="number" class="form-control" wire:model="anno" min="1990" max="{{ date('Y') + 1 }}">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Colore</label>
                <input type="text" class="form-control" wire:model="colore">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Carburante *</label>
                <select class="form-control" wire:model="carburante_tipo">
                  <option value="benzina">Benzina</option>
                  <option value="diesel">Diesel</option>
                  <option value="ibrido">Ibrido</option>
                  <option value="elettrico">Elettrico</option>
                  <option value="gpl">GPL</option>
                  <option value="metano">Metano</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Km attuali *</label>
                <input type="number" class="form-control @error('km_attuali') is-invalid @enderror"
                  wire:model="km_attuali" min="0">
                @error('km_attuali')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Livello carburante inizio (%) *</label>
                <input type="range" class="form-control-range" wire:model="livello_carburante_inizio"
                  min="0" max="100" step="5">
                <small class="text-muted">{{ $livello_carburante_inizio }}%</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Immagine</label>
                <input type="file" class="form-control-file" wire:model="immagine" accept="image/*">
                @error('immagine')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-2 d-flex align-items-center">
              <div class="form-check mt-3">
                <input type="checkbox" class="form-check-input" id="chkAttivo" wire:model="attivo">
                <label class="form-check-label" for="chkAttivo">Attivo</label>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea class="form-control" wire:model="note" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
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
