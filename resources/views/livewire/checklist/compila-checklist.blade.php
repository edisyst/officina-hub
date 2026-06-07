<div>

  {{-- Header riepilogo --}}
  <div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <strong class="mr-3">{{ $this->commessa->numero }}</strong>
        <span class="text-muted">{{ $this->commessa->cliente->nome ?? '' }} {{ $this->commessa->cliente->cognome ?? '' }}</span>
        <span class="badge badge-secondary ml-2">{{ $this->commessa->veicolo->targa ?? $this->commessa->veicolo->marca ?? '' }}</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        @if($this->compilata->isCompletata())
          <span class="badge badge-success py-2 px-3">
            <i class="fas fa-check-circle mr-1"></i>
            Completata {{ $this->compilata->completata_at->format('d/m/Y H:i') }}
          </span>
        @else
          <span class="text-muted small">{{ $this->compilata->percentualeCompletamento() }}% compilato</span>
          <button wire:click="completa" class="btn btn-success btn-tablet">
            <i class="fas fa-flag-checkered"></i> Finalizza
          </button>
        @endif
        <a href="{{ route('marcatempo') }}" class="btn btn-outline-secondary btn-tablet">
          <i class="fas fa-arrow-left"></i>
        </a>
      </div>
    </div>
  </div>

  @error('completamento')
    <div class="alert alert-danger">{{ $message }}</div>
  @enderror

  {{-- Voci della checklist --}}
  <div class="row">
    @foreach($voci as $voce)
      @php $risposta = $rispostePerId->get($voce->id) @endphp
      <div class="col-md-6 mb-3">
        <div class="card {{ $risposta ? 'border-success' : ($voce->obbligatoria ? 'border-warning' : '') }}">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <strong>{{ $voce->etichetta }}</strong>
              <div class="d-flex gap-1">
                @if($voce->obbligatoria)
                  <span class="badge badge-warning">obblig.</span>
                @endif
                @if($risposta)
                  <span class="badge badge-success"><i class="fas fa-check"></i></span>
                @endif
              </div>
            </div>

            @if(!$this->compilata->isCompletata())

              {{-- Si/No --}}
              @if($voce->tipo === 'si_no')
                <div class="d-flex gap-2">
                  <button type="button"
                          wire:click="$set('risposte.{{ $voce->id }}', true); $nextTick(() => salvaRisposta({{ $voce->id }}))"
                          class="btn btn-tablet flex-grow-1 {{ isset($risposte[$voce->id]) && $risposte[$voce->id] === true ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="fas fa-check fa-lg"></i> Sì
                  </button>
                  <button type="button"
                          wire:click="$set('risposte.{{ $voce->id }}', false); $nextTick(() => salvaRisposta({{ $voce->id }}))"
                          class="btn btn-tablet flex-grow-1 {{ isset($risposte[$voce->id]) && $risposte[$voce->id] === false ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="fas fa-times fa-lg"></i> No
                  </button>
                </div>

              {{-- Numerico --}}
              @elseif($voce->tipo === 'numerico')
                <div class="input-group">
                  <input type="number" step="0.01"
                         wire:model="risposte.{{ $voce->id }}"
                         wire:change="salvaRisposta({{ $voce->id }})"
                         class="form-control form-control-lg"
                         placeholder="Inserisci valore">
                  @if($voce->unita_misura)
                    <div class="input-group-append">
                      <span class="input-group-text">{{ $voce->unita_misura }}</span>
                    </div>
                  @endif
                </div>

              {{-- Testo libero --}}
              @elseif($voce->tipo === 'testo_libero')
                <textarea wire:model="risposte.{{ $voce->id }}"
                          wire:change="salvaRisposta({{ $voce->id }})"
                          class="form-control"
                          rows="3"
                          placeholder="Note..."></textarea>

              {{-- Foto --}}
              @elseif($voce->tipo === 'foto_obbligatoria')
                <div>
                  <input type="file" accept="image/*" capture="environment"
                         wire:model="fotoTemp.{{ $voce->id }}"
                         class="form-control-file mb-2">
                  <button wire:click="salvaFoto({{ $voce->id }})"
                          class="btn btn-primary btn-sm"
                          @if(!isset($fotoTemp[$voce->id])) disabled @endif>
                    <i class="fas fa-upload mr-1"></i> Carica foto
                  </button>
                </div>
              @endif

            @else
              {{-- Vista sola lettura quando completata --}}
              @if($risposta)
                @if($voce->tipo === 'si_no')
                  <span class="badge badge-{{ $risposta->valore_booleano ? 'success' : 'danger' }} py-2 px-3">
                    {{ $risposta->valore_booleano ? 'Sì' : 'No' }}
                  </span>
                @elseif($voce->tipo === 'numerico')
                  <strong>{{ $risposta->valore_numerico }} {{ $voce->unita_misura }}</strong>
                @elseif($voce->tipo === 'testo_libero')
                  <p class="mb-0">{{ $risposta->valore_testo }}</p>
                @elseif($voce->tipo === 'foto_obbligatoria' && $risposta->foto_path)
                  <a href="{{ route('checklist.foto', basename($risposta->foto_path)) }}" target="_blank"
                     class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-image mr-1"></i> Visualizza foto
                  </a>
                @endif
              @else
                <span class="text-muted">—</span>
              @endif
            @endif

          </div>
        </div>
      </div>
    @endforeach
  </div>

</div>
