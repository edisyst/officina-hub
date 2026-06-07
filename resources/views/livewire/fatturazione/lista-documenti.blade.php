<div>
  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Documenti</h3>
      <div class="card-tools d-flex align-items-center gap-2">
        @can('create', \App\Models\Documento::class)
        <button wire:click="$set('showGeneraModal', true)" class="btn btn-sm btn-primary mr-2">
          <i class="fas fa-plus mr-1"></i> Genera fattura
        </button>
        @endcan
        <button wire:click="esportaCsv" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-file-csv mr-1"></i> CSV
        </button>
      </div>
    </div>

    {{-- Filtri --}}
    <div class="card-header bg-light py-2">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <input type="text" wire:model.live.debounce.300ms="search"
                 class="form-control form-control-sm" placeholder="Cerca numero, cliente...">
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroTipo" class="form-control form-control-sm">
            <option value="">Tutti i tipi</option>
            @foreach($tipi as $t)
              <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroStato" class="form-control form-control-sm">
            <option value="">Tutti gli stati</option>
            @foreach($stati as $s)
              <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroAnno" class="form-control form-control-sm">
            <option value="">Tutti gli anni</option>
            @foreach($anni as $anno)
              <option value="{{ $anno }}">{{ $anno }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      @if(session('success'))
        <div class="alert alert-success m-3 alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          {{ session('success') }}
        </div>
      @endif

      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Numero</th>
              <th>Tipo</th>
              <th>Data</th>
              <th>Cliente</th>
              <th class="text-right">Imponibile</th>
              <th class="text-right">IVA</th>
              <th class="text-right">Totale</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($documenti as $doc)
            <tr>
              <td><strong>{{ $doc->numero }}</strong></td>
              <td><span class="badge {{ $doc->tipo->badgeClass() }}">{{ $doc->tipo->label() }}</span></td>
              <td>{{ $doc->data_emissione->format('d/m/Y') }}</td>
              <td>{{ $doc->cliente->nome_completo }}</td>
              <td class="text-right">€ {{ number_format((float)$doc->imponibile, 2, ',', '.') }}</td>
              <td class="text-right">€ {{ number_format((float)$doc->iva_totale, 2, ',', '.') }}</td>
              <td class="text-right"><strong>€ {{ number_format((float)$doc->totale, 2, ',', '.') }}</strong></td>
              <td><span class="badge {{ $doc->stato->badgeClass() }}">{{ $doc->stato->label() }}</span></td>
              <td class="text-right">
                <a href="{{ route('fatturazione.documenti.show', $doc->id) }}" class="btn btn-xs btn-outline-info">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">Nessun documento trovato.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($documenti->hasPages())
    <div class="card-footer">
      {{ $documenti->links() }}
    </div>
    @endif
  </div>

  {{-- MODAL: Genera fattura da commessa --}}
  @if($showGeneraModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Genera fattura da commessa</h5>
          <button wire:click="$set('showGeneraModal', false)" type="button" class="close"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Cerca commessa (stato: completata o consegnata)</label>
            <input type="text" class="form-control" wire:model.live.debounce.300ms="commessaSearch"
                   placeholder="Numero commessa o cliente...">
          </div>
          @error('commessaSelezionataId') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
          @foreach($commesseDisponibili as $c)
          <div wire:click="$set('commessaSelezionataId', {{ $c->id }})"
               class="border rounded p-2 mb-1 cursor-pointer {{ $commessaSelezionataId == $c->id ? 'border-primary bg-light' : '' }}"
               style="cursor:pointer">
            <strong>{{ $c->numero }}</strong> — {{ $c->cliente->nome_completo }}
            <span class="badge badge-secondary float-right">{{ $c->stato->label() }}</span>
          </div>
          @endforeach
          @if(strlen($commessaSearch) >= 2 && $commesseDisponibili->isEmpty())
            <p class="text-muted small">Nessuna commessa trovata in stato Completata/Consegnata.</p>
          @endif
        </div>
        <div class="modal-footer">
          <button wire:click="$set('showGeneraModal', false)" class="btn btn-secondary">Annulla</button>
          <button wire:click="generaFattura" class="btn btn-primary" {{ !$commessaSelezionataId ? 'disabled' : '' }}>
            Genera fattura
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
