<div>
  @if (session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  @if($importMessaggio)
  <div class="alert {{ $importErrore ? 'alert-warning' : 'alert-success' }} alert-dismissible">
    <button type="button" class="close" wire:click="$set('importMessaggio', null)">&times;</button>
    {{ $importMessaggio }}
  </div>
  @endif

  <!-- Barra filtri e azioni -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Tariffe Manodopera</h3>
      <div class="card-tools d-flex gap-2">
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-download"></i> Esporta CSV
        </button>
        <button wire:click="apriNuovo" class="btn btn-sm btn-primary">
          <i class="fas fa-plus"></i> Nuova Tariffa
        </button>
      </div>
    </div>
    <div class="card-body border-bottom pb-3">
      <div class="row">
        <div class="col-md-5">
          <input wire:model.live.debounce.300ms="cerca" type="text"
            class="form-control form-control-sm"
            placeholder="Cerca per codice, descrizione o categoria…">
        </div>
        <div class="col-md-3">
          <select wire:model.live="filtroCategoria" class="form-control form-control-sm">
            <option value="">Tutte le categorie</option>
            @foreach($categorie as $cat)
            <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroTipoVeicolo" class="form-control form-control-sm">
            <option value="">Tutti i veicoli</option>
            <option value="auto">Auto</option>
            <option value="moto">Moto</option>
            <option value="entrambi">Entrambi</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="btn btn-sm btn-outline-info w-100 mb-0">
            <i class="fas fa-upload"></i> Importa CSV
            <input type="file" wire:model="csvFile" class="d-none" accept=".csv,.txt">
          </label>
          @error('csvFile')<small class="text-danger d-block">{{ $message }}</small>@enderror
          @if($csvFile)
          <button wire:click="importaCsv" class="btn btn-sm btn-info w-100 mt-1" wire:loading.attr="disabled">
            <span wire:loading wire:target="importaCsv" class="spinner-border spinner-border-sm"></span>
            Importa
          </button>
          @endif
        </div>
      </div>
      <div class="mt-1">
        <a wire:click.prevent="scaricaTemplate" href="#" class="small text-muted">
          <i class="fas fa-file-csv"></i> Scarica template CSV
        </a>
      </div>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Codice</th>
            <th>Descrizione</th>
            <th>Categoria</th>
            <th class="text-center">Min.</th>
            <th class="text-right">Listino €</th>
            <th class="text-center">IVA %</th>
            <th class="text-center">Veicolo</th>
            <th class="text-center">Stato</th>
            <th class="text-center">Azioni</th>
          </tr>
        </thead>
        <tbody>
          @forelse($tariffe as $t)
          <tr wire:key="tariffa-{{ $t->id }}" class="{{ $t->attivo ? '' : 'text-muted' }}">
            <td><code>{{ $t->codice }}</code></td>
            <td>{{ $t->descrizione }}</td>
            <td><span class="badge badge-light">{{ $t->categoria }}</span></td>
            <td class="text-center">{{ $t->minuti_standard }}'</td>
            <td class="text-right font-weight-bold">€ {{ number_format((float)$t->prezzo_listino, 2, ',', '.') }}</td>
            <td class="text-center">{{ $t->iva_percentuale }}%</td>
            <td class="text-center">
              @if($t->tipo_veicolo === 'auto') <span class="badge badge-primary">Auto</span>
              @elseif($t->tipo_veicolo === 'moto') <span class="badge badge-warning">Moto</span>
              @else <span class="badge badge-secondary">Entrambi</span>
              @endif
            </td>
            <td class="text-center">
              <span wire:click="toggleAttivo({{ $t->id }})" style="cursor:pointer"
                class="badge {{ $t->attivo ? 'badge-success' : 'badge-secondary' }}">
                {{ $t->attivo ? 'Attiva' : 'Inattiva' }}
              </span>
            </td>
            <td class="text-center">
              <button wire:click="apriModifica({{ $t->id }})" class="btn btn-xs btn-outline-primary">
                <i class="fas fa-edit"></i>
              </button>
              <button wire:click="elimina({{ $t->id }})"
                wire:confirm="Eliminare la tariffa {{ $t->codice }}?"
                class="btn btn-xs btn-outline-danger">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center text-muted py-4">Nessuna tariffa trovata.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      {{ $tariffe->links() }}
    </div>
  </div>

  <!-- Modal Crea/Modifica -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $tariffaId ? 'Modifica Tariffa' : 'Nuova Tariffa' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Codice <span class="text-danger">*</span></label>
                <input wire:model="codice" type="text" class="form-control @error('codice') is-invalid @enderror"
                  placeholder="es. MOD-001" style="text-transform:uppercase">
                @error('codice')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-9">
              <div class="form-group">
                <label>Descrizione <span class="text-danger">*</span></label>
                <input wire:model="descrizione" type="text" class="form-control @error('descrizione') is-invalid @enderror">
                @error('descrizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Categoria <span class="text-danger">*</span></label>
                <input wire:model="categoria" type="text" list="categorie-list" class="form-control @error('categoria') is-invalid @enderror">
                <datalist id="categorie-list">
                  @foreach($categorie as $cat)
                  <option value="{{ $cat }}">
                  @endforeach
                </datalist>
                @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Minuti std. <span class="text-danger">*</span></label>
                <input wire:model="minuti_standard" type="number" min="1" class="form-control @error('minuti_standard') is-invalid @enderror">
                @error('minuti_standard')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Listino € <span class="text-danger">*</span></label>
                <input wire:model="prezzo_listino" type="number" step="0.01" min="0" class="form-control @error('prezzo_listino') is-invalid @enderror">
                @error('prezzo_listino')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>IVA %</label>
                <input wire:model="iva_percentuale" type="number" step="0.01" class="form-control">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Tipo veicolo</label>
                <select wire:model="tipo_veicolo" class="form-control">
                  <option value="entrambi">Entrambi</option>
                  <option value="auto">Auto</option>
                  <option value="moto">Moto</option>
                </select>
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
