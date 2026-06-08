<div>
  {{-- Ricerca veicolo --}}
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-search mr-2"></i>Ricerca veicolo</h3>
    </div>
    <div class="card-body">
      <div class="input-group" style="max-width:400px">
        <input wire:model="targaRicerca" type="text" class="form-control" placeholder="Targa veicolo..."
               wire:keydown.enter="cercaVeicolo">
        <div class="input-group-append">
          <button wire:click="cercaVeicolo" class="btn btn-primary">
            <i class="fas fa-search"></i> Cerca
          </button>
        </div>
      </div>
      @error('targaRicerca')
        <div class="text-danger mt-1 small">{{ $message }}</div>
      @enderror
    </div>
  </div>

  @if($veicolo)
  {{-- Info veicolo --}}
  <div class="alert alert-info">
    <strong><i class="fas fa-car mr-1"></i>{{ $veicolo->targa }}</strong>
    — {{ $veicolo->marca }} {{ $veicolo->modello }}
    @if($veicolo->clientePrincipale)
      — Cliente: {{ $veicolo->clientePrincipale->nome_completo }}
    @endif
  </div>

  {{-- Stato attuale --}}
  <div class="row">
    <div class="col-md-6">
      <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title">Montati sul veicolo ({{ $montati->count() }})</h3></div>
        <div class="card-body p-0">
          @forelse($montati as $p)
          <div class="p-2 border-bottom">
            <span class="badge badge-warning mr-1">{{ $p->stagione->label() }}</span>
            <strong>{{ $p->marca }}</strong> {{ $p->misura }}
          </div>
          @empty
          <p class="text-muted p-2">Nessun set montato registrato.</p>
          @endforelse
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">In deposito ({{ $inDeposito->count() }})</h3></div>
        <div class="card-body p-0">
          @forelse($inDeposito as $p)
          @php $ult = $p->movimenti->first(); @endphp
          <div class="p-2 border-bottom">
            <span class="badge badge-info mr-1">{{ $p->stagione->label() }}</span>
            <strong>{{ $p->marca }}</strong> {{ $p->misura }}
            @if($ult && $ult->ubicazione)
              <small class="text-muted ml-1">— {{ $ult->ubicazione }}</small>
            @endif
          </div>
          @empty
          <p class="text-muted p-2">Nessun set in deposito.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  @if($operazioneCompletata)
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-check-circle mr-1"></i> {{ $messaggioEsito }}
  </div>
  @endif

  {{-- Pulsanti azione --}}
  <div class="row mb-3">
    <div class="col">
      <button wire:click="selezionaAzione('deposita')"
              class="btn btn-outline-primary mr-2 {{ $azioneAttiva === 'deposita' ? 'active' : '' }}">
        <i class="fas fa-box"></i> Deposita set
      </button>
      <button wire:click="selezionaAzione('monta')"
              class="btn btn-outline-success mr-2 {{ $azioneAttiva === 'monta' ? 'active' : '' }}">
        <i class="fas fa-car"></i> Monta da deposito
      </button>
      <button wire:click="selezionaAzione('nuovo')"
              class="btn btn-outline-info mr-2 {{ $azioneAttiva === 'nuovo' ? 'active' : '' }}">
        <i class="fas fa-plus"></i> Registra set nuovo
      </button>
      <button wire:click="selezionaAzione('smaltisci')"
              class="btn btn-outline-danger {{ $azioneAttiva === 'smaltisci' ? 'active' : '' }}">
        <i class="fas fa-trash"></i> Smaltisci set
      </button>
    </div>
  </div>

  {{-- Form deposita --}}
  @if($azioneAttiva === 'deposita')
  <div class="card card-warning card-outline">
    <div class="card-header"><h3 class="card-title">Deposita set</h3></div>
    <div class="card-body">
      <div class="form-group">
        <label>Seleziona set da depositare *</label>
        <select wire:model="pneumaticoSelezionatoId"
                class="form-control @error('pneumaticoSelezionatoId') is-invalid @enderror">
          <option value="">— Seleziona —</option>
          @foreach($montati as $p)
          <option value="{{ $p->id }}">{{ $p->stagione->label() }} — {{ $p->marca }} {{ $p->misura }}</option>
          @endforeach
        </select>
        @error('pneumaticoSelezionatoId')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Ubicazione fisica</label>
            <input wire:model="ubicazione" type="text" class="form-control" placeholder="es. Scaffale A3, Posizione 7">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>KM al momento</label>
            <input wire:model="km_deposito" type="number" class="form-control" placeholder="km veicolo">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Usura: <strong wire:ignore.self id="usura_label">{{ $usura_percentuale }}%</strong></label>
        <input wire:model="usura_percentuale" type="range" class="form-control-range" min="0" max="100"
               oninput="document.getElementById('usura_label').textContent = this.value + '%'">
        <small class="text-muted">0% = nuova, 100% = da sostituire</small>
      </div>
      <div class="form-group">
        <label>Note usura</label>
        <input wire:model="usura_note" type="text" class="form-control" placeholder="es. Battistrada 3mm ant sx">
      </div>
      <div class="form-group">
        <label>Note</label>
        <textarea wire:model="note_deposito" class="form-control" rows="2"></textarea>
      </div>
      <div class="d-flex justify-content-end">
        <button wire:click="$set('azioneAttiva', '')" class="btn btn-secondary mr-2">Annulla</button>
        <button wire:click="eseguiDeposita" class="btn btn-warning">
          <i class="fas fa-box"></i> Conferma deposito
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Form monta da deposito --}}
  @if($azioneAttiva === 'monta')
  <div class="card card-success card-outline">
    <div class="card-header"><h3 class="card-title">Monta set da deposito</h3></div>
    <div class="card-body">
      <div class="form-group">
        <label>Seleziona set da montare *</label>
        <select wire:model="pneumaticoSelezionatoId"
                class="form-control @error('pneumaticoSelezionatoId') is-invalid @enderror">
          <option value="">— Seleziona —</option>
          @foreach($inDeposito as $p)
          @php $ult = $p->movimenti->first(); @endphp
          <option value="{{ $p->id }}">
            {{ $p->stagione->label() }} — {{ $p->marca }} {{ $p->misura }}
            {{ $ult && $ult->ubicazione ? '(' . $ult->ubicazione . ')' : '' }}
          </option>
          @endforeach
        </select>
        @error('pneumaticoSelezionatoId')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group" style="max-width:250px">
        <label>KM attuali veicolo</label>
        <input wire:model="km_monta" type="number" class="form-control" placeholder="km">
      </div>
      <div class="d-flex justify-content-end">
        <button wire:click="$set('azioneAttiva', '')" class="btn btn-secondary mr-2">Annulla</button>
        <button wire:click="eseguiMonta" class="btn btn-success">
          <i class="fas fa-car"></i> Conferma montaggio
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Form set nuovo --}}
  @if($azioneAttiva === 'nuovo')
  <div class="card card-info card-outline">
    <div class="card-header"><h3 class="card-title">Registra set nuovo</h3></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Stagione *</label>
            <select wire:model="stagione_nuovo"
                    class="form-control @error('stagione_nuovo') is-invalid @enderror">
              <option value="estivo">Estivo</option>
              <option value="invernale">Invernale</option>
              <option value="quattro_stagioni">4 Stagioni</option>
            </select>
            @error('stagione_nuovo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Marca *</label>
            <input wire:model="marca_nuovo" type="text"
                   class="form-control @error('marca_nuovo') is-invalid @enderror" placeholder="es. Pirelli">
            @error('marca_nuovo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Misura *</label>
            <input wire:model="misura_nuovo" type="text"
                   class="form-control @error('misura_nuovo') is-invalid @enderror" placeholder="205/55 R16">
            @error('misura_nuovo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Note</label>
        <textarea wire:model="note_nuovo" class="form-control" rows="2"></textarea>
      </div>
      <div class="d-flex justify-content-end">
        <button wire:click="$set('azioneAttiva', '')" class="btn btn-secondary mr-2">Annulla</button>
        <button wire:click="eseguiNuovoSet" class="btn btn-info">
          <i class="fas fa-plus"></i> Registra set
        </button>
      </div>
    </div>
  </div>
  @endif

  {{-- Form smaltimento --}}
  @if($azioneAttiva === 'smaltisci')
  <div class="card card-danger card-outline">
    <div class="card-header"><h3 class="card-title">Smaltisci set</h3></div>
    <div class="card-body">
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Ricorda di compilare il documento PFU (D.Lgs. 152/2006) per i pneumatici fuori uso.
      </div>
      <div class="form-group">
        <label>Seleziona set da smaltire *</label>
        <select wire:model="pneumaticoSelezionatoId"
                class="form-control @error('pneumaticoSelezionatoId') is-invalid @enderror">
          <option value="">— Seleziona —</option>
          @foreach($pneumatici->whereIn('stato.value', ['montato', 'in_deposito']) as $p)
          <option value="{{ $p->id }}">
            {{ $p->stagione->label() }} — {{ $p->marca }} {{ $p->misura }} ({{ $p->stato->label() }})
          </option>
          @endforeach
        </select>
        @error('pneumaticoSelezionatoId')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="d-flex justify-content-end">
        <button wire:click="$set('azioneAttiva', '')" class="btn btn-secondary mr-2">Annulla</button>
        <button wire:click="eseguiSmaltimento" class="btn btn-danger"
                onclick="return confirm('Confermi lo smaltimento? Operazione irreversibile.')">
          <i class="fas fa-trash"></i> Conferma smaltimento
        </button>
      </div>
    </div>
  </div>
  @endif
  @endif
</div>
