<div>
  {{-- Flash messages --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('error') }}
    </div>
  @endif

  {{-- Totali periodo --}}
  <div class="row mb-3">
    <div class="col-md-4">
      <div class="info-box bg-success">
        <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Entrate periodo</span>
          <span class="info-box-number">€ {{ number_format($totali['entrate'], 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="info-box bg-danger">
        <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Uscite periodo</span>
          <span class="info-box-number">€ {{ number_format($totali['uscite'], 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="info-box {{ $totali['saldo'] >= 0 ? 'bg-primary' : 'bg-warning' }}">
        <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Saldo periodo</span>
          <span class="info-box-number">€ {{ number_format($totali['saldo'], 2, ',', '.') }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="card card-outline card-secondary">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-book-open mr-2"></i>Prima Nota</h3>
      <div class="card-tools">
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary mr-1">
          <i class="fas fa-file-csv mr-1"></i> Esporta CSV
        </button>
        <button wire:click="apriModal" class="btn btn-sm btn-primary">
          <i class="fas fa-plus mr-1"></i> Aggiungi movimento
        </button>
      </div>
    </div>

    {{-- Filtri --}}
    <div class="card-header bg-light py-2">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="small">Dal</label>
          <input type="date" wire:model.live="filtroDal" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="small">Al</label>
          <input type="date" wire:model.live="filtroAl" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="small">Tipo</label>
          <select wire:model.live="filtroTipo" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($tipi as $t)
              <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="small">Metodo</label>
          <select wire:model.live="filtroMetodo" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($metodi as $m)
              <option value="{{ $m->value }}">{{ $m->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="small">Conto</label>
          <select wire:model.live="filtroConto" class="form-control form-control-sm">
            <option value="">Tutti</option>
            @foreach($conti as $c)
              <option value="{{ $c->value }}">{{ $c->label() }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Data</th>
              <th>Causale</th>
              <th>Tipo</th>
              <th>Metodo</th>
              <th>Conto</th>
              <th class="text-right">Importo</th>
              <th>Fonte</th>
              <th class="text-center">Azioni</th>
            </tr>
          </thead>
          <tbody>
            @forelse($movimenti as $m)
            <tr>
              <td>{{ $m->data->format('d/m/Y') }}</td>
              <td>
                {{ $m->causale }}
                @if($m->note)
                  <small class="text-muted d-block">{{ Str::limit($m->note, 60) }}</small>
                @endif
              </td>
              <td>
                <span class="badge {{ $m->tipo->badgeClass() }}">{{ $m->tipo->label() }}</span>
              </td>
              <td class="small">{{ $m->metodo->label() }}</td>
              <td class="small">{{ $m->conto->label() }}</td>
              <td class="text-right font-weight-bold">€ {{ number_format((float)$m->importo, 2, ',', '.') }}</td>
              <td>
                @if($m->automatico)
                  <span class="badge badge-info">automatico</span>
                @else
                  <span class="badge badge-secondary">manuale</span>
                @endif
                @if($m->documento_id)
                  <a href="{{ route('fatturazione.documenti.show', $m->documento_id) }}" class="badge badge-light" target="_blank">
                    fattura
                  </a>
                @endif
              </td>
              <td class="text-center">
                @if(!$m->automatico)
                  <button wire:click="modificaMovimento({{ $m->id }})" class="btn btn-xs btn-outline-primary mr-1" title="Modifica">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button wire:click="elimina({{ $m->id }})"
                    wire:confirm="Eliminare questo movimento?"
                    class="btn btn-xs btn-outline-danger" title="Elimina">
                    <i class="fas fa-trash"></i>
                  </button>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Nessun movimento nel periodo selezionato.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer">
      {{ $movimenti->links() }}
    </div>
  </div>

  {{-- Modal aggiungi/modifica movimento --}}
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica movimento' : 'Aggiungi movimento manuale' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Data *</label>
            <input type="date" wire:model="data" class="form-control @error('data') is-invalid @enderror">
            @error('data')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label>Causale *</label>
            <input type="text" wire:model="causale" class="form-control @error('causale') is-invalid @enderror" placeholder="Descrizione del movimento">
            @error('causale')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tipo *</label>
                <select wire:model="tipo" class="form-control @error('tipo') is-invalid @enderror">
                  @foreach($tipi as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                  @endforeach
                </select>
                @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Importo (€) *</label>
                <input type="number" step="0.01" wire:model="importo" class="form-control @error('importo') is-invalid @enderror">
                @error('importo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Metodo *</label>
                <select wire:model.live="metodo" class="form-control @error('metodo') is-invalid @enderror">
                  @foreach($metodi as $m)
                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                  @endforeach
                </select>
                @error('metodo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Conto *</label>
                <select wire:model="conto" class="form-control @error('conto') is-invalid @enderror">
                  @foreach($conti as $c)
                    <option value="{{ $c->value }}">{{ $c->label() }}</option>
                  @endforeach
                </select>
                @error('conto')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
          <button type="button" class="btn btn-primary" wire:click="salva">
            <span wire:loading.remove wire:target="salva">Salva</span>
            <span wire:loading wire:target="salva"><i class="fas fa-spinner fa-spin mr-1"></i>Salvataggio...</span>
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
