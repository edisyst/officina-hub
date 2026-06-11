<div>
  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Ordini fornitori</h3>
      <div class="card-tools">
        <a href="{{ route('acquisti.genera-ordini') }}" class="btn btn-sm btn-outline-info mr-1">
          <i class="fas fa-magic mr-1"></i> Genera da sottoscorta
        </a>
        <a href="{{ route('acquisti.ordini.create') }}" class="btn btn-sm btn-primary">
          <i class="fas fa-plus mr-1"></i> Nuovo ordine
        </a>
      </div>
    </div>

    <div class="card-header bg-light py-2">
      <div class="row g-2">
        <div class="col-md-3">
          <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Cerca numero o fornitore...">
        </div>
        <div class="col-md-3">
          <select wire:model.live="filtroFornitore" class="form-control form-control-sm">
            <option value="">Tutti i fornitori</option>
            @foreach($fornitori as $f)
              <option value="{{ $f->id }}">{{ $f->ragione_sociale }}</option>
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
            @foreach($anni as $a)
              <option value="{{ $a }}">{{ $a }}</option>
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
              <th>Fornitore</th>
              <th>Data</th>
              <th>Consegna prevista</th>
              <th>Righe</th>
              <th>Stato</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($ordini as $ordine)
            <tr>
              <td><strong>{{ $ordine->numero }}</strong></td>
              <td>{{ $ordine->fornitore->ragione_sociale }}</td>
              <td>{{ $ordine->data_ordine->format('d/m/Y') }}</td>
              <td>{{ $ordine->data_consegna_prevista?->format('d/m/Y') ?? '—' }}</td>
              <td>{{ $ordine->righe_count ?? '—' }}</td>
              <td><span class="badge {{ $ordine->stato->badgeClass() }}">{{ $ordine->stato->label() }}</span></td>
              <td class="text-right">
                <a href="{{ route('acquisti.ordini.show', $ordine->id) }}" class="btn btn-xs btn-outline-primary">
                  <i class="fas fa-eye"></i>
                </a>
                @if(!in_array($ordine->stato->value, ['ricevuto','annullato']))
                <a href="{{ route('acquisti.ordini.ricevi', $ordine->id) }}" class="btn btn-xs btn-outline-success">
                  <i class="fas fa-truck-loading"></i>
                </a>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Nessun ordine trovato.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($ordini->hasPages())
    <div class="card-footer">{{ $ordini->links() }}</div>
    @endif
  </div>
</div>
