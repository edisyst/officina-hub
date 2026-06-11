<div>
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  <div class="card card-outline card-success">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-truck-loading mr-2"></i>
        Ricezione merce — {{ $ordine->numero }}
        ({{ $ordine->fornitore->ragione_sociale }})
      </h3>
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label>Numero DDT fornitore *</label>
            <input type="text" wire:model="numeroDdt" class="form-control" placeholder="Es: DDT-2024-001">
            @error('numeroDdt') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Data DDT *</label>
            <input type="date" wire:model="dataDdt" class="form-control">
            @error('dataDdt') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Data ricezione *</label>
            <input type="date" wire:model="dataRicezione" class="form-control">
            @error('dataRicezione') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group">
            <label>Note</label>
            <input type="text" wire:model="note" class="form-control" placeholder="Note ricezione...">
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="thead-light">
            <tr>
              <th>Descrizione</th>
              <th>Cod. fornitore</th>
              <th class="text-right">Q.tà ordinata</th>
              <th class="text-right">Già ricevuta</th>
              <th class="text-right">Da ricevere</th>
              <th style="width:120px">Q.tà ricevuta ora</th>
              <th style="width:120px">Prezzo acquisto €</th>
            </tr>
          </thead>
          <tbody>
            @foreach($righe as $i => $riga)
            @php
              $completa = $riga['da_ricevere'] <= 0;
            @endphp
            <tr class="{{ $completa ? 'table-success' : '' }}">
              <td>{{ $riga['descrizione'] }}</td>
              <td>{{ $riga['codice_fornitore'] ?? '—' }}</td>
              <td class="text-right">{{ $riga['quantita_ordinata'] }}</td>
              <td class="text-right">{{ $riga['quantita_ricevuta_totale'] }}</td>
              <td class="text-right">
                @if($completa)
                  <span class="badge badge-success">Completo</span>
                @else
                  {{ $riga['da_ricevere'] }}
                @endif
              </td>
              <td>
                <input type="number" min="0" max="{{ $riga['da_ricevere'] }}"
                       wire:model="righe.{{ $i }}.quantita_ricevuta"
                       class="form-control form-control-sm"
                       {{ $completa ? 'disabled' : '' }}>
              </td>
              <td>
                <input type="number" step="0.01" min="0"
                       wire:model="righe.{{ $i }}.prezzo_unitario"
                       class="form-control form-control-sm">
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer">
      <button wire:click="confermaRicezione" class="btn btn-success">
        <i class="fas fa-check mr-1"></i> Conferma ricezione
      </button>
      <a href="{{ route('acquisti.ordini.show', $ordine->id) }}" class="btn btn-secondary ml-2">
        <i class="fas fa-arrow-left mr-1"></i> Annulla
      </a>
    </div>
  </div>
</div>
