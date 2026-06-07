<div>
  <!-- Intestazione articolo -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h4 class="mb-1">
            {{ $articolo->descrizione }}
            @if(!$articolo->attivo)
            <span class="badge badge-secondary ml-2">Inattivo</span>
            @endif
          </h4>
          <p class="text-muted mb-0">
            Codice: <strong>{{ $articolo->codice }}</strong>
            @if($articolo->codice_fornitore) — Cod. fornitore: {{ $articolo->codice_fornitore }} @endif
          </p>
          <small class="text-muted">
            Categoria: {{ $articolo->categoria?->nome ?? '—' }} |
            Fornitore: {{ $articolo->fornitore?->ragione_sociale ?? '—' }} |
            Ubicazione: {{ $articolo->ubicazione ?? '—' }}
          </small>
        </div>
        <div class="col-md-3">
          <div class="info-box bg-{{ $articolo->isSottoScorta() ? 'danger' : 'success' }} mb-0">
            <span class="info-box-icon"><i class="fas fa-boxes"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Giacenza</span>
              <span class="info-box-number">{{ $articolo->giacenza_attuale }} {{ $articolo->unita_misura->label() }}</span>
              <div class="progress"><div class="progress-bar" style="width:100%"></div></div>
              <span class="progress-description">Scorta min.: {{ $articolo->scorta_minima }}</span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted">Prezzo acquisto</td><td class="text-right font-weight-bold">€ {{ number_format((float)$articolo->prezzo_acquisto, 2, ',', '.') }}</td></tr>
            <tr><td class="text-muted">Prezzo vendita</td><td class="text-right font-weight-bold">€ {{ number_format((float)$articolo->prezzo_vendita, 2, ',', '.') }}</td></tr>
            <tr><td class="text-muted">IVA</td><td class="text-right">{{ $articolo->iva_percentuale }}%</td></tr>
          </table>
        </div>
      </div>
    </div>
    @can('movimenta', $articolo)
    <div class="card-footer">
      <button wire:click="apriCaricoModal('carico')" class="btn btn-sm btn-success">
        <i class="fas fa-arrow-down"></i> Carico
      </button>
      <button wire:click="apriCaricoModal('reso_fornitore')" class="btn btn-sm btn-info">
        <i class="fas fa-undo"></i> Reso fornitore
      </button>
      <button wire:click="apriCaricoModal('reso_cliente')" class="btn btn-sm btn-secondary">
        <i class="fas fa-undo"></i> Reso cliente
      </button>
      <button wire:click="apriRettificaModal()" class="btn btn-sm btn-warning">
        <i class="fas fa-balance-scale"></i> Rettifica inventario
      </button>
    </div>
    @endcan
  </div>

  @if($articolo->descrizione_estesa)
  <div class="card mb-3">
    <div class="card-body">
      <p class="mb-0">{{ $articolo->descrizione_estesa }}</p>
    </div>
  </div>
  @endif

  <!-- Storico movimenti -->
  <div class="card">
    <div class="card-header">
      <h6 class="mb-0"><i class="fas fa-history mr-2"></i>Storico movimenti</h6>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th class="text-right">Quantità</th>
            <th class="text-right">Giacenza prec.</th>
            <th class="text-right">Giacenza succ.</th>
            <th>Commessa</th>
            <th>Utente</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          @forelse($movimenti as $mov)
          <tr>
            <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
            <td><span class="badge {{ $mov->tipo->badgeClass() }}">{{ $mov->tipo->label() }}</span></td>
            <td class="text-right font-weight-bold">{{ $mov->quantita }}</td>
            <td class="text-right text-muted">{{ $mov->giacenza_precedente }}</td>
            <td class="text-right">{{ $mov->giacenza_successiva }}</td>
            <td>
              @if($mov->commessa)
              <a href="{{ route('commesse.show', $mov->commessa->id) }}">{{ $mov->commessa->numero }}</a>
              @else
              @if($mov->documento_fornitore) {{ $mov->documento_fornitore }} @else — @endif
              @endif
            </td>
            <td><small>{{ $mov->user?->name ?? '—' }}</small></td>
            <td><small class="{{ str_contains($mov->note ?? '', 'NEGATIVO') ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $mov->note }}</small></td>
          </tr>
          @empty
          <tr><td colspan="8" class="text-center text-muted py-3">Nessun movimento registrato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($movimenti->hasPages())
    <div class="card-footer">{{ $movimenti->links() }}</div>
    @endif
  </div>

  <!-- Modal Carico/Reso -->
  @if($showCaricoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            @if($caricoTipo === 'carico') Carico magazzino
            @elseif($caricoTipo === 'reso_fornitore') Reso a fornitore
            @else Reso da cliente
            @endif
          </h5>
          <button type="button" class="close" wire:click="$set('showCaricoModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Quantità *</label>
                <input wire:model="caricoQuantita" type="number" min="1" step="1"
                  class="form-control @error('caricoQuantita') is-invalid @enderror">
                @error('caricoQuantita')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Prezzo unitario €</label>
                <input wire:model="caricoPrezzoUnitario" type="number" step="0.01" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Documento fornitore</label>
                <input wire:model="caricoDocumento" type="text" class="form-control" placeholder="DDT, fattura…">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Data documento</label>
                <input wire:model="caricoDataDocumento" type="date" class="form-control">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="caricoNote" class="form-control" rows="2"></textarea>
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
          <p class="text-muted">Giacenza attuale: <strong>{{ $articolo->giacenza_attuale }}</strong></p>
          <div class="form-group">
            <label>Nuova giacenza *</label>
            <input wire:model="nuovaGiacenza" type="number" min="0" step="1"
              class="form-control @error('nuovaGiacenza') is-invalid @enderror">
            @error('nuovaGiacenza')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Motivo rettifica *</label>
            <textarea wire:model="rettificaNota" rows="3"
              class="form-control @error('rettificaNota') is-invalid @enderror"
              placeholder="Motivazione obbligatoria…"></textarea>
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
</div>
