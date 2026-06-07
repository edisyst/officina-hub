<div>
  @if(session('success'))
  <div class="alert alert-success alert-dismissible py-2">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  {{-- ── SEZIONE SINISTRO ────────────────────────────────────────────────────── --}}
  <div class="card card-outline card-secondary mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-car-crash mr-2"></i>Dati Sinistro</h3>
      <div class="card-tools">
        @if($sinistro)
          <span class="badge {{ $sinistro->stato->badgeClass() }} mr-2">{{ $sinistro->stato->label() }}</span>
        @endif
        <button wire:click="apriFormSinistro" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-{{ $sinistro ? 'edit' : 'plus' }} mr-1"></i>
          {{ $sinistro ? 'Modifica' : 'Apri Sinistro' }}
        </button>
      </div>
    </div>

    @if($editSinistro)
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Compagnia Assicurativa</label>
            <select wire:model="compagnia_assicurativa_id" class="form-control form-control-sm">
              <option value="">— Seleziona —</option>
              @foreach($compagnie as $c)
              <option value="{{ $c->id }}">{{ $c->nome }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Tipo Sinistro <span class="text-danger">*</span></label>
            <select wire:model="tipo_sinistro" class="form-control form-control-sm @error('tipo_sinistro') is-invalid @enderror">
              <option value="">— Seleziona —</option>
              @foreach($tipiSinistro as $tipo)
              <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
              @endforeach
            </select>
            @error('tipo_sinistro')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Stato</label>
            <select wire:model="stato" class="form-control form-control-sm">
              @foreach($statiSinistro as $s)
              <option value="{{ $s->value }}">{{ $s->label() }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>N° Sinistro</label>
            <input type="text" wire:model="numero_sinistro" class="form-control form-control-sm" placeholder="Numero sinistro">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>N° Polizza Cliente</label>
            <input type="text" wire:model="numero_polizza_cliente" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>N° Polizza Controparte</label>
            <input type="text" wire:model="numero_polizza_controparte" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Data Sinistro</label>
            <input type="date" wire:model="data_sinistro" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-5">
          <div class="form-group">
            <label>Luogo Sinistro</label>
            <input type="text" wire:model="luogo_sinistro" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Liquidatore — Nome</label>
            <input type="text" wire:model="liquidatore_nome" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Liquidatore — Email</label>
            <input type="email" wire:model="liquidatore_email" class="form-control form-control-sm @error('liquidatore_email') is-invalid @enderror">
            @error('liquidatore_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Liquidatore — Telefono</label>
            <input type="text" wire:model="liquidatore_telefono" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-12">
          <div class="form-group">
            <label>Dinamica del sinistro</label>
            <textarea wire:model="descrizione_dinamica" class="form-control form-control-sm" rows="2" placeholder="Descrizione di come è avvenuto il sinistro..."></textarea>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-sm btn-secondary mr-2" wire:click="$set('editSinistro', false)">Annulla</button>
        <button type="button" class="btn btn-sm btn-primary" wire:click="salvaSinistro" wire:loading.attr="disabled">
          <span wire:loading wire:target="salvaSinistro" class="spinner-border spinner-border-sm mr-1"></span>
          Salva Sinistro
        </button>
      </div>
    </div>

    @elseif($sinistro)
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <small class="text-muted d-block">Compagnia</small>
          <strong>{{ $sinistro->compagniaAssicurativa?->nome ?? '—' }}</strong>
        </div>
        <div class="col-md-4">
          <small class="text-muted d-block">N° Sinistro</small>
          <strong>{{ $sinistro->numero_sinistro ?? '—' }}</strong>
        </div>
        <div class="col-md-4">
          <small class="text-muted d-block">Tipo</small>
          <strong>{{ $sinistro->tipo_sinistro->label() }}</strong>
        </div>
        @if($sinistro->data_sinistro)
        <div class="col-md-4 mt-2">
          <small class="text-muted d-block">Data Sinistro</small>
          <strong>{{ $sinistro->data_sinistro->format('d/m/Y') }}</strong>
        </div>
        @endif
        @if($sinistro->luogo_sinistro)
        <div class="col-md-4 mt-2">
          <small class="text-muted d-block">Luogo</small>
          <strong>{{ $sinistro->luogo_sinistro }}</strong>
        </div>
        @endif
        @if($sinistro->liquidatore_nome)
        <div class="col-md-4 mt-2">
          <small class="text-muted d-block">Liquidatore</small>
          <strong>{{ $sinistro->liquidatore_nome }}</strong>
          @if($sinistro->liquidatore_email)
          <small class="text-muted d-block">{{ $sinistro->liquidatore_email }}</small>
          @endif
        </div>
        @endif
      </div>
    </div>
    @else
    <div class="card-body text-muted text-center py-3">
      Nessun sinistro collegato. Clicca "Apri Sinistro" per aggiungerne uno.
    </div>
    @endif
  </div>

  {{-- ── SEZIONE PERIZIA ─────────────────────────────────────────────────────── --}}
  @if($sinistro)
  <div class="card card-outline card-warning">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-file-contract mr-2"></i>Perizia</h3>
      <div class="card-tools">
        @if($perizia)
          @if($perizia->accettata === true)
            <span class="badge badge-success mr-2">Accettata</span>
          @elseif($perizia->accettata === false)
            <span class="badge badge-danger mr-2">Contestata</span>
          @else
            <span class="badge badge-secondary mr-2">In Attesa</span>
          @endif
        @endif
        <button wire:click="apriFormPerizia" class="btn btn-sm btn-outline-warning">
          <i class="fas fa-{{ $perizia ? 'edit' : 'plus' }} mr-1"></i>
          {{ $perizia ? 'Modifica' : 'Inserisci Perizia' }}
        </button>
      </div>
    </div>

    @if($editPerizia)
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Nome Perito</label>
            <input type="text" wire:model="perito_nome" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Email Perito</label>
            <input type="email" wire:model="perito_email" class="form-control form-control-sm @error('perito_email') is-invalid @enderror">
            @error('perito_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Data Sopralluogo</label>
            <input type="date" wire:model="data_sopralluogo" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Data Ricezione Perizia</label>
            <input type="date" wire:model="data_ricezione" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Importo Liquidato (€)</label>
            <input type="number" step="0.01" wire:model.live="importo_liquidato"
              class="form-control form-control-sm @error('importo_liquidato') is-invalid @enderror">
            @error('importo_liquidato')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Franchigia (€)</label>
            <input type="number" step="0.01" wire:model.live="importo_franchigia" class="form-control form-control-sm">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Scoperto (%)</label>
            <input type="number" step="0.01" wire:model.live="importo_scoperto_percentuale" class="form-control form-control-sm" min="0" max="100">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Importo Netto Liquidato</label>
            @php
              $netto = null;
              if ($importo_liquidato !== '') {
                $n = (float)$importo_liquidato - (float)$importo_franchigia;
                if ((float)$importo_scoperto_percentuale > 0) {
                  $n = $n * (1 - (float)$importo_scoperto_percentuale / 100);
                }
                $netto = max(0, $n);
              }
            @endphp
            <div class="form-control form-control-sm bg-light text-{{ $netto !== null ? 'success' : 'muted' }}">
              {{ $netto !== null ? '€ ' . number_format($netto, 2, ',', '.') : '—' }}
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Esito Perizia</label>
            <select wire:model="accettata" class="form-control form-control-sm">
              <option value="">In attesa</option>
              <option value="1">Accettata</option>
              <option value="0">Contestata</option>
            </select>
          </div>
        </div>
        @if($accettata === '0' || $accettata === 0 || $accettata === false)
        <div class="col-md-8">
          <div class="form-group">
            <label>Motivo Contestazione</label>
            <input type="text" wire:model="motivo_contestazione" class="form-control form-control-sm">
          </div>
        </div>
        @endif
        <div class="col-12">
          <div class="form-group">
            <label>Note Perito</label>
            <textarea wire:model="note_perito" class="form-control form-control-sm" rows="2"></textarea>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Allega PDF Perizia</label>
            <input type="file" wire:model="allegatoPerizia" class="form-control-file @error('allegatoPerizia') is-invalid @enderror" accept=".pdf">
            @error('allegatoPerizia')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($perizia?->allegato_perizia_path)
            <small class="text-muted">File attuale: {{ basename($perizia->allegato_perizia_path) }}</small>
            @endif
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-sm btn-secondary mr-2" wire:click="$set('editPerizia', false)">Annulla</button>
        <button type="button" class="btn btn-sm btn-warning" wire:click="salvaPerizia" wire:loading.attr="disabled">
          <span wire:loading wire:target="salvaPerizia" class="spinner-border spinner-border-sm mr-1"></span>
          Salva Perizia
        </button>
      </div>
    </div>

    @elseif($perizia)
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <small class="text-muted d-block">Perito</small>
          <strong>{{ $perizia->perito_nome ?? '—' }}</strong>
        </div>
        <div class="col-md-3">
          <small class="text-muted d-block">Sopralluogo</small>
          <strong>{{ $perizia->data_sopralluogo?->format('d/m/Y') ?? '—' }}</strong>
        </div>
        <div class="col-md-3">
          <small class="text-muted d-block">Importo Liquidato</small>
          <strong>€ {{ number_format($perizia->importo_liquidato ?? 0, 2, ',', '.') }}</strong>
        </div>
        <div class="col-md-3">
          <small class="text-muted d-block">Importo Netto</small>
          <strong class="text-success">€ {{ number_format($perizia->importo_netto_liquidato ?? 0, 2, ',', '.') }}</strong>
        </div>
        @if($perizia->importo_franchigia > 0)
        <div class="col-md-3 mt-2">
          <small class="text-muted d-block">Franchigia</small>
          <strong>€ {{ number_format($perizia->importo_franchigia, 2, ',', '.') }}</strong>
        </div>
        @endif
        @if($perizia->allegato_perizia_path)
        <div class="col-md-4 mt-2">
          <a href="{{ route('allegati.perizia', basename($perizia->allegato_perizia_path)) }}" target="_blank"
            class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-pdf mr-1"></i> Scarica PDF Perizia
          </a>
        </div>
        @endif
      </div>
    </div>
    @else
    <div class="card-body text-muted text-center py-3">
      Nessuna perizia inserita.
    </div>
    @endif
  </div>
  @endif
</div>
