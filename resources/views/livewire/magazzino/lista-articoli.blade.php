<div>
  <!-- Filtri -->
  <div class="card card-body mb-3">
    <div class="row g-2">
      <div class="col-md-4">
        <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm"
          placeholder="Cerca codice, descrizione, cod. fornitore…">
      </div>
      <div class="col-md-2">
        <select wire:model.live="filtroCategoria" class="form-control form-control-sm">
          <option value="">Tutte le categorie</option>
          @foreach($categorie as $cat)
          <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select wire:model.live="filtroFornitore" class="form-control form-control-sm">
          <option value="">Tutti i fornitori</option>
          @foreach($fornitori as $f)
          <option value="{{ $f->id }}">{{ $f->ragione_sociale }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-center">
        <div class="custom-control custom-switch">
          <input wire:model.live="soloSottoScorta" type="checkbox" class="custom-control-input" id="switchSottoScorta">
          <label class="custom-control-label" for="switchSottoScorta">Solo sotto scorta</label>
        </div>
      </div>
      <div class="col-md-2 text-right">
        @can('create', \App\Models\Articolo::class)
        <button wire:click="apriArticoloModal()" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i> Nuovo articolo
        </button>
        @endcan
      </div>
    </div>
  </div>

  <!-- Barra azioni bulk -->
  @if(count($selectedIds) > 0 || $selectAll)
  <div class="alert alert-info py-2 mb-3 d-flex align-items-center flex-wrap" style="gap:8px">
    <span>
      <strong>{{ $selectionCount }}</strong> articol{{ $selectionCount === 1 ? 'o' : 'i' }} selezionat{{ $selectionCount === 1 ? 'o' : 'i' }}.
    </span>
    @if(!$selectAll && $articoli->total() > count($selectedIds))
    <button wire:click="selectAllResults" class="btn btn-sm btn-link p-0">
      Seleziona tutti i {{ $articoli->total() }} risultati
    </button>
    @endif
    <div class="ml-auto d-flex flex-wrap" style="gap:6px">
      @can('create', \App\Models\Articolo::class)
      <button wire:click="bulkRiordina" class="btn btn-sm btn-warning" wire:loading.attr="disabled" wire:target="bulkRiordina">
        <span wire:loading wire:target="bulkRiordina"><i class="fas fa-spinner fa-spin"></i></span>
        <i class="fas fa-cart-plus"></i> Riordina
      </button>
      <button wire:click="apriBulkUbicazioneModal" class="btn btn-sm btn-info">
        <i class="fas fa-map-marker-alt"></i> Ubicazione
      </button>
      @endcan
      <button wire:click="exportCsv" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-file-csv"></i> CSV
      </button>
      <button wire:click="deselectAll" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-times"></i> Deseleziona
      </button>
    </div>
  </div>
  @endif

  @include('livewire.workspace._saved-filters-bar')

  <!-- Tabella -->
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0 table-sm">
        <thead class="thead-light">
          <tr>
            <th style="width:36px">
              <input type="checkbox" wire:model.live="selectPage" title="Seleziona pagina">
            </th>
            <th>Codice</th>
            <th>Descrizione</th>
            <th>Categoria</th>
            <th>Fornitore</th>
            <th>U.M.</th>
            <th class="text-right">Giacenza</th>
            <th class="text-right">Prezzo acq.</th>
            <th class="text-right">Prezzo vend. <i class="fas fa-pencil-alt fa-xs text-muted" title="Modificabile inline"></i></th>
            <th>Ubicazione <i class="fas fa-pencil-alt fa-xs text-muted" title="Modificabile inline"></i></th>
            <th>Scorta min. <i class="fas fa-pencil-alt fa-xs text-muted" title="Modificabile inline"></i></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($articoli as $art)
          <tr class="{{ in_array($art->id, $selectedIds) ? 'table-active' : '' }}">
            <td>
              <input type="checkbox" wire:model.live="selectedIds" value="{{ $art->id }}">
            </td>
            <td>
              <a href="{{ route('magazzino.articoli.show', $art->id) }}" class="font-weight-bold">
                {{ $art->codice }}
              </a>
              @if(!$art->attivo)<span class="badge badge-secondary ml-1">Inattivo</span>@endif
            </td>
            <td>{{ $art->descrizione }}</td>
            <td><small>{{ $art->categoria?->nome ?? '—' }}</small></td>
            <td><small>{{ $art->fornitore?->ragione_sociale ?? '—' }}</small></td>
            <td>{{ $art->unita_misura->label() }}</td>
            <td class="text-right">
              <span class="font-weight-bold {{ $art->isSottoScorta() ? 'text-danger' : '' }}">
                {{ $art->giacenza_attuale }}
                @if($art->isSottoScorta())
                <i class="fas fa-exclamation-triangle ml-1" title="Sotto scorta minima ({{ $art->scorta_minima }})"></i>
                @endif
              </span>
            </td>
            <td class="text-right">€ {{ number_format((float)$art->prezzo_acquisto, 2, ',', '.') }}</td>
            <td class="text-right" style="min-width:90px">
              @can('update', $art)
              <x-inline-edit :record-id="$art->id" field="prezzo_vendita" :value="(float)$art->prezzo_vendita" type="number" step="0.01" min="0" />
              @else
              € {{ number_format((float)$art->prezzo_vendita, 2, ',', '.') }}
              @endcan
            </td>
            <td style="min-width:100px">
              @can('update', $art)
              <x-inline-edit :record-id="$art->id" field="ubicazione" :value="$art->ubicazione ?? ''" placeholder="—" />
              @else
              <small>{{ $art->ubicazione ?? '—' }}</small>
              @endcan
            </td>
            <td style="min-width:70px">
              @can('update', $art)
              <x-inline-edit :record-id="$art->id" field="scorta_minima" :value="(int)$art->scorta_minima" type="number" step="1" min="0" />
              @else
              {{ $art->scorta_minima }}
              @endcan
            </td>
            <td class="text-right text-nowrap">
              @can('movimenta', $art)
              <button wire:click="apriCaricoModal({{ $art->id }})" class="btn btn-xs btn-success" title="Carico">
                <i class="fas fa-arrow-down"></i>
              </button>
              <button wire:click="apriRettificaModal({{ $art->id }})" class="btn btn-xs btn-warning" title="Rettifica">
                <i class="fas fa-balance-scale"></i>
              </button>
              @endcan
              @can('update', $art)
              <button wire:click="apriArticoloModal({{ $art->id }})" class="btn btn-xs btn-info" title="Modifica">
                <i class="fas fa-edit"></i>
              </button>
              @endcan
              @can('delete', $art)
              <button wire:click="eliminaArticolo({{ $art->id }})"
                wire:confirm="Eliminare/disattivare l'articolo «{{ $art->descrizione }}»?"
                class="btn btn-xs btn-danger" title="Elimina">
                <i class="fas fa-trash"></i>
              </button>
              @endcan
            </td>
          </tr>
          @empty
          <tr><td colspan="12" class="text-center text-muted py-4">Nessun articolo trovato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($articoli->hasPages())
    <div class="card-footer">{{ $articoli->links() }}</div>
    @endif
  </div>

  <!-- Modal Articolo -->
  @if($showArticoloModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica articolo' : 'Nuovo articolo' }}</h5>
          <button type="button" class="close" wire:click="$set('showArticoloModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Codice interno *</label>
                <input wire:model="codice" type="text" class="form-control form-control-sm @error('codice') is-invalid @enderror">
                @error('codice')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Codice fornitore</label>
                <input wire:model="codice_fornitore" type="text" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Descrizione *</label>
                <input wire:model="descrizione" type="text" class="form-control form-control-sm @error('descrizione') is-invalid @enderror">
                @error('descrizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Categoria</label>
                <select wire:model="categoria_articolo_id" class="form-control form-control-sm">
                  <option value="">— Nessuna —</option>
                  @foreach($categorie as $cat)
                  <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fornitore preferenziale</label>
                <select wire:model="fornitore_id" class="form-control form-control-sm">
                  <option value="">— Nessuno —</option>
                  @foreach($fornitori as $f)
                  <option value="{{ $f->id }}">{{ $f->ragione_sociale }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Unità misura *</label>
                <select wire:model="unita_misura" class="form-control form-control-sm">
                  @foreach($unitaMisura as $um)
                  <option value="{{ $um->value }}">{{ $um->value }} — {{ $um->label() }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>IVA %</label>
                <input wire:model="iva_percentuale" type="number" step="0.01" class="form-control form-control-sm">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Prezzo acquisto €</label>
                <input wire:model="prezzo_acquisto" type="number" step="0.01" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Prezzo vendita €</label>
                <input wire:model="prezzo_vendita" type="number" step="0.01" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Scorta minima</label>
                <input wire:model="scorta_minima" type="number" step="1" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Scorta massima</label>
                <input wire:model="scorta_massima" type="number" step="1" class="form-control form-control-sm" placeholder="—">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Ubicazione</label>
                <input wire:model="ubicazione" type="text" class="form-control form-control-sm" placeholder="Es. A3-S2">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Descrizione estesa</label>
                <textarea wire:model="descrizione_estesa" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Note</label>
                <textarea wire:model="note_articolo" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input wire:model="attivo" type="checkbox" class="custom-control-input" id="switchAttivo">
              <label class="custom-control-label" for="switchAttivo">Articolo attivo</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showArticoloModal',false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salvaArticolo" wire:loading.attr="disabled">
            <span wire:loading wire:target="salvaArticolo" class="spinner-border spinner-border-sm mr-1"></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Modal Carico/Reso -->
  @if($showCaricoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Registra movimento</h5>
          <button type="button" class="close" wire:click="$set('showCaricoModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tipo *</label>
            <select wire:model.live="caricoTipo" class="form-control form-control-sm">
              @foreach($tipiCarico as $t)
              <option value="{{ $t->value }}">{{ $t->label() }}</option>
              @endforeach
            </select>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Quantità *</label>
                <input wire:model="caricoQuantita" type="number" min="1" step="1" class="form-control form-control-sm @error('caricoQuantita') is-invalid @enderror">
                @error('caricoQuantita')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Prezzo unitario €</label>
                <input wire:model="caricoPrezzoUnitario" type="number" step="0.01" class="form-control form-control-sm">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Documento fornitore</label>
                <input wire:model="caricoDocumento" type="text" class="form-control form-control-sm" placeholder="DDT, fattura…">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Data documento</label>
                <input wire:model="caricoDataDocumento" type="date" class="form-control form-control-sm">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="caricoNote" class="form-control form-control-sm" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showCaricoModal',false)">Annulla</button>
          <button type="button" class="btn btn-success" wire:click="eseguiCarico" wire:loading.attr="disabled">
            <span wire:loading wire:target="eseguiCarico" class="spinner-border spinner-border-sm mr-1"></span>
            Registra
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Modal Rettifica -->
  @if($showRettificaModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Rettifica inventario</h5>
          <button type="button" class="close" wire:click="$set('showRettificaModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nuova giacenza *</label>
            <input wire:model="nuovaGiacenza" type="number" min="0" step="1"
              class="form-control @error('nuovaGiacenza') is-invalid @enderror">
            @error('nuovaGiacenza')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Motivo rettifica *</label>
            <textarea wire:model="rettificaNota" class="form-control @error('rettificaNota') is-invalid @enderror" rows="3"
              placeholder="Motivazione obbligatoria (es. conteggio fisico, perdita…)"></textarea>
            @error('rettificaNota')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showRettificaModal',false)">Annulla</button>
          <button type="button" class="btn btn-warning" wire:click="eseguiRettifica" wire:loading.attr="disabled">
            <span wire:loading wire:target="eseguiRettifica" class="spinner-border spinner-border-sm mr-1"></span>
            Conferma rettifica
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Modal Bulk Ubicazione -->
  @if($showBulkUbicazioneModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Aggiorna ubicazione — {{ $selectionCount }} articoli</h5>
          <button type="button" class="close" wire:click="$set('showBulkUbicazioneModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nuova ubicazione *</label>
            <input wire:model="bulkNuovaUbicazione" type="text" class="form-control @error('bulkNuovaUbicazione') is-invalid @enderror" placeholder="Es. A3-S2">
            @error('bulkNuovaUbicazione')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showBulkUbicazioneModal',false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="eseguiBulkUbicazione" class="btn btn-info" wire:loading.attr="disabled">
            <span wire:loading wire:target="eseguiBulkUbicazione"><i class="fas fa-spinner fa-spin"></i></span>
            Aggiorna
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Report Riordino -->
  @if($showBulkReport && !empty($bulkReport))
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Report riordino</h5>
          <button type="button" class="close" wire:click="$set('showBulkReport',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          @if(!empty($bulkReport['ordini']))
          <p class="text-success">
            <i class="fas fa-check-circle"></i>
            <strong>{{ count($bulkReport['ordini']) }}</strong> ordini fornitore in bozza creati.
          </p>
          <p><a href="{{ route('acquisti.ordini') }}" class="btn btn-sm btn-outline-primary">Vai agli ordini</a></p>
          @endif
          @if(!empty($bulkReport['senza_fornitore']))
          <p class="text-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>{{ count($bulkReport['senza_fornitore']) }}</strong> articoli senza fornitore preferenziale: non inclusi negli ordini.
          </p>
          @endif
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showBulkReport',false)" class="btn btn-primary">Chiudi</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
