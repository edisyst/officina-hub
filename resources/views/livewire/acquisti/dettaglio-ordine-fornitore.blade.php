<div>
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-file-alt mr-2"></i>
        {{ $ordine ? $ordine->numero : 'Nuovo ordine fornitore' }}
      </h3>
      @if($ordine)
      <div class="card-tools">
        <span class="badge {{ $ordine->stato->badgeClass() }} mr-2">{{ $ordine->stato->label() }}</span>
        <button wire:click="stampaPdf" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-print mr-1"></i> Stampa PDF
        </button>
      </div>
      @endif
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Fornitore *</label>
            <select wire:model="fornitoreId" class="form-control"
              {{ $ordine && !$ordine->stato->modificabile() ? 'disabled' : '' }}>
              <option value="0">-- Seleziona fornitore --</option>
              @foreach($fornitori as $f)
                <option value="{{ $f->id }}">{{ $f->ragione_sociale }}</option>
              @endforeach
            </select>
            @error('fornitoreId') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Data consegna prevista</label>
            <input type="date" wire:model="dataConsegnaPrevista" class="form-control">
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group">
            <label>Note</label>
            <input type="text" wire:model="note" class="form-control" placeholder="Note ordine...">
          </div>
        </div>
      </div>

      {{-- Ricerca articolo --}}
      @if(!$ordine || $ordine->stato->modificabile())
      <div class="form-group" x-data="{ open: false }">
        <label>Aggiungi articolo (cerca per codice o descrizione)</label>
        <div class="input-group">
          <input type="text" wire:model.live.debounce.300ms="ricercaArticolo"
                 class="form-control" placeholder="Cerca articolo..."
                 @focus="open = true" @blur="setTimeout(() => open = false, 200)">
          <div class="input-group-append">
            <button wire:click="aggiungiRiga()" type="button" class="btn btn-outline-secondary">
              <i class="fas fa-plus"></i> Riga vuota
            </button>
          </div>
        </div>
        @if(count($articoliSuggeriti) > 0)
        <div class="dropdown-menu show w-100" style="max-height:250px;overflow-y:auto">
          @foreach($articoliSuggeriti as $art)
          <button type="button" class="dropdown-item" wire:click="selezionaArticolo({{ $art['id'] }})">
            <strong>{{ $art['codice'] }}</strong> — {{ $art['descrizione'] }}
            @if($art['codice_fornitore']) <small class="text-muted">(cod. forn: {{ $art['codice_fornitore'] }})</small> @endif
          </button>
          @endforeach
        </div>
        @endif
      </div>
      @endif

      {{-- Righe --}}
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="thead-light">
            <tr>
              <th>Descrizione</th>
              <th style="width:130px">Cod. fornitore</th>
              <th style="width:100px">Q.tà ordinata</th>
              <th style="width:100px">Q.tà ricevuta</th>
              <th style="width:120px">Prezzo atteso €</th>
              <th style="width:40px"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($righe as $i => $riga)
            <tr>
              <td>
                <input type="text" wire:model="righe.{{ $i }}.descrizione" class="form-control form-control-sm"
                  {{ $ordine && !$ordine->stato->modificabile() ? 'disabled' : '' }}>
              </td>
              <td>
                <input type="text" wire:model="righe.{{ $i }}.codice_fornitore" class="form-control form-control-sm"
                  {{ $ordine && !$ordine->stato->modificabile() ? 'disabled' : '' }}>
              </td>
              <td>
                <input type="number" min="1" wire:model="righe.{{ $i }}.quantita_ordinata" class="form-control form-control-sm"
                  {{ $ordine && !$ordine->stato->modificabile() ? 'disabled' : '' }}>
              </td>
              <td class="text-center align-middle text-muted">{{ $riga['quantita_ricevuta'] ?? 0 }}</td>
              <td>
                <input type="number" step="0.01" wire:model="righe.{{ $i }}.prezzo_unitario_atteso" class="form-control form-control-sm"
                  {{ $ordine && !$ordine->stato->modificabile() ? 'disabled' : '' }}>
              </td>
              <td>
                @if(!$ordine || $ordine->stato->modificabile())
                <button wire:click="rimuoviRiga({{ $i }})" type="button" class="btn btn-xs btn-outline-danger">
                  <i class="fas fa-times"></i>
                </button>
                @endif
              </td>
            </tr>
            @endforeach
            @if(empty($righe))
            <tr><td colspan="6" class="text-center text-muted">Nessuna riga.</td></tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer">
      @if(!$ordine || $ordine->stato->modificabile())
      <button wire:click="salva" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Salva
      </button>
      @endif

      @if($ordine && $ordine->stato->value === 'bozza')
      <button wire:click="segnaInviato" class="btn btn-info ml-2">
        <i class="fas fa-paper-plane mr-1"></i> Segna come inviato
      </button>
      @endif

      @if($ordine && $ordine->stato->value === 'inviato')
      <button wire:click="segnaConfermato" class="btn btn-success ml-2">
        <i class="fas fa-check mr-1"></i> Conferma ordine
      </button>
      @endif

      @if($ordine && !in_array($ordine->stato->value, ['ricevuto','annullato']))
      <a href="{{ route('acquisti.ordini.ricevi', $ordine->id) }}" class="btn btn-outline-success ml-2">
        <i class="fas fa-truck-loading mr-1"></i> Ricevi merce
      </a>
      @endif

      <a href="{{ route('acquisti.ordini') }}" class="btn btn-secondary ml-2">
        <i class="fas fa-arrow-left mr-1"></i> Torna alla lista
      </a>
    </div>
  </div>
</div>
