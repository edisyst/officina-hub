<div>
  @if (session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  <!-- Header Commessa -->
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">
        Commessa <strong>{{ $commessa->numero }}</strong>
        <span class="badge {{ $commessa->stato->badgeClass() }} ml-2">{{ $commessa->stato->label() }}</span>
        <span class="badge badge-secondary ml-1">{{ $commessa->tipo->label() }}</span>
      </h3>
      <div class="card-tools">
        <!-- Pulsanti di transizione stato -->
        @foreach($commessa->stato->transizioniAmmesse() as $statoTarget)
          @php
            $abilita = match($statoTarget) {
              \App\Enums\StatoCommessa::Accettata => 'accetta',
              \App\Enums\StatoCommessa::InLavorazione => $commessa->stato === \App\Enums\StatoCommessa::Accettata ? 'avviaLavori' : 'riprendi',
              \App\Enums\StatoCommessa::Sospesa => 'sospendi',
              \App\Enums\StatoCommessa::Completata => 'completa',
              \App\Enums\StatoCommessa::Consegnata => 'consegna',
              \App\Enums\StatoCommessa::Fatturata => 'fattura',
              default => null,
            };
            $btnClass = match($statoTarget) {
              \App\Enums\StatoCommessa::Accettata => 'btn-info',
              \App\Enums\StatoCommessa::InLavorazione => 'btn-primary',
              \App\Enums\StatoCommessa::Sospesa => 'btn-warning',
              \App\Enums\StatoCommessa::Completata => 'btn-success',
              \App\Enums\StatoCommessa::Consegnata => 'btn-dark',
              \App\Enums\StatoCommessa::Fatturata => 'btn-secondary',
              default => 'btn-outline-secondary',
            };
          @endphp
          @if($abilita === null || Gate::check($abilita, $commessa))
          <button wire:click="apriTransizione('{{ $statoTarget->value }}')"
            class="btn btn-sm {{ $btnClass }} mr-1">
            {{ $statoTarget->label() }}
          </button>
          @endif
        @endforeach

        <!-- PDF buttons -->
        @hasanyrole('admin|accettatore')
        <a href="{{ route('cortesia.consegna.commessa', $commessa->id) }}" class="btn btn-sm btn-outline-primary ml-1">
          <i class="fas fa-car-side"></i> Cortesia
        </a>
        @endhasanyrole
        @hasanyrole('admin|accettatore|meccanico')
        <a href="{{ route('deposito.commessa', $commessa->id) }}" class="btn btn-sm btn-outline-info ml-1">
          <i class="fas fa-circle-notch"></i> Pneumatici
        </a>
        @endhasanyrole
        <a href="{{ route('pdf.scheda', $commessa->id) }}" class="btn btn-sm btn-outline-danger" target="_blank">
          <i class="fas fa-file-pdf"></i> Scheda
        </a>
        <a href="{{ route('pdf.preventivo', $commessa->id) }}" class="btn btn-sm btn-outline-secondary ml-1" target="_blank">
          <i class="fas fa-file-pdf"></i> Preventivo
        </a>
      </div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <h6 class="text-muted text-uppercase mb-1">Cliente</h6>
          <p class="mb-0">
            <a href="{{ route('clienti.show', $commessa->cliente_id) }}" class="font-weight-bold">
              {{ $commessa->cliente->nome_completo }}
            </a>
            <br>
            @if($commessa->cliente->telefono)
            <i class="fas fa-phone text-muted"></i> {{ $commessa->cliente->telefono }}
            @endif
          </p>
        </div>
        <div class="col-md-4">
          <h6 class="text-muted text-uppercase mb-1">Veicolo</h6>
          <p class="mb-0">
            <a href="{{ route('veicoli.show', $commessa->veicolo_id) }}" class="font-weight-bold">
              {{ $commessa->veicolo->targa ?? 'Senza Targa' }}
            </a>
            <br>
            {{ $commessa->veicolo->marca }} {{ $commessa->veicolo->modello }}
            @if($commessa->km_ingresso)
            — <small class="text-muted">{{ number_format($commessa->km_ingresso) }} km</small>
            @endif
          </p>
        </div>
        <div class="col-md-4">
          <h6 class="text-muted text-uppercase mb-1">Date</h6>
          <p class="mb-0">
            Ingresso: <strong>{{ $commessa->data_ingresso->format('d/m/Y H:i') }}</strong><br>
            @if($commessa->data_uscita_prevista)
            Uscita prevista: <strong>{{ $commessa->data_uscita_prevista->format('d/m/Y') }}</strong><br>
            @endif
            @if($commessa->data_consegna)
            Consegnato: <strong>{{ $commessa->data_consegna->format('d/m/Y H:i') }}</strong>
            @endif
          </p>
        </div>
      </div>
      @if($commessa->descrizione_cliente)
      <hr>
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-muted text-uppercase mb-1">Descrizione del cliente</h6>
          <p class="mb-0">{{ $commessa->descrizione_cliente }}</p>
        </div>
        @if($commessa->diagnosi_tecnica)
        <div class="col-md-6">
          <h6 class="text-muted text-uppercase mb-1">Diagnosi tecnica</h6>
          <p class="mb-0">{{ $commessa->diagnosi_tecnica }}</p>
        </div>
        @endif
      </div>
      @endif
    </div>
  </div>

  <!-- Tab Section -->
  <ul class="nav nav-tabs" id="commessaTabs">
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'lavorazioni' ? 'active' : '' }}"
        wire:click="$set('tabAttiva', 'lavorazioni')" href="#">
        <i class="fas fa-tools mr-1"></i> Preventivo
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'tempi' ? 'active' : '' }}"
        wire:click="$set('tabAttiva', 'tempi')" href="#">
        <i class="fas fa-stopwatch mr-1"></i> Lavorazioni & Tempi
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'allegati' ? 'active' : '' }}"
        wire:click="$set('tabAttiva', 'allegati')" href="#">
        <i class="fas fa-paperclip mr-1"></i> Allegati
        <span class="badge badge-secondary">{{ $commessa->allegati->count() }}</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'log' ? 'active' : '' }}"
        wire:click="$set('tabAttiva', 'log')" href="#">
        <i class="fas fa-history mr-1"></i> Log
      </a>
    </li>
    @php
      $checklistAttivi = \App\Models\ChecklistTemplate::attivi()->get();
      $checklistFatte  = \App\Models\ChecklistCompilata::where('commessa_id', $commessa->id)->whereNotNull('completata_at')->count();
    @endphp
    @if($checklistAttivi->count() > 0)
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'checklist' ? 'active' : '' }}"
        wire:click="$set('tabAttiva', 'checklist')" href="#">
        <i class="fas fa-tasks mr-1"></i> Checklist
        <span class="badge {{ $checklistFatte === $checklistAttivi->count() ? 'badge-success' : 'badge-secondary' }}">
          {{ $checklistFatte }}/{{ $checklistAttivi->count() }}
        </span>
      </a>
    </li>
    @endif
    @if($commessa->tipo === \App\Enums\TipoCommessa::Carrozzeria)
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'carrozzeria' ? 'active' : '' }}"
        wire:click="$set('tabAttiva', 'carrozzeria')" href="#">
        <i class="fas fa-car-crash mr-1"></i> Carrozzeria
      </a>
    </li>
    @endif
    @php
      $dviCount = \App\Models\DviIspezione::where('commessa_id', $commessa->id)->count();
      $dviInAttesa = \App\Models\DviIspezione::where('commessa_id', $commessa->id)
          ->where('stato', \App\Enums\StatoDviIspezione::InviataCliente)->count();
    @endphp
    <li class="nav-item">
      <a class="nav-link {{ $tabAttiva === 'dvi' ? 'active' : '' }}"
        wire:click="$set('tabAttiva', 'dvi')" href="#">
        <i class="fas fa-camera-retro mr-1"></i> DVI
        @if($dviInAttesa > 0)
        <span class="badge badge-warning">{{ $dviInAttesa }}</span>
        @elseif($dviCount > 0)
        <span class="badge badge-secondary">{{ $dviCount }}</span>
        @endif
      </a>
    </li>
  </ul>

  <div class="tab-content border border-top-0 p-3 bg-white">
    @if($tabAttiva === 'lavorazioni')
    <livewire:commesse.gestione-righe :commessa-id="$commessa->id" :key="'righe-'.$commessa->id" />
    @elseif($tabAttiva === 'tempi')
    <livewire:marcatempo.gestione-lavorazioni :commessa-id="$commessa->id" :key="'lav-'.$commessa->id" />
    @elseif($tabAttiva === 'allegati')
    <livewire:commesse.gestione-allegati :commessa-id="$commessa->id" :key="'allegati-'.$commessa->id" />
    @elseif($tabAttiva === 'log')
    <div>
      @forelse($commessa->log as $entry)
      <div class="d-flex align-items-start mb-3">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width:36px;height:36px;min-width:36px">
          <i class="fas fa-history fa-sm"></i>
        </div>
        <div>
          <div>
            @if($entry->stato_da)
            <span class="badge {{ $entry->stato_da->badgeClass() }}">{{ $entry->stato_da->label() }}</span>
            <i class="fas fa-arrow-right mx-1 text-muted"></i>
            @endif
            <span class="badge {{ $entry->stato_a->badgeClass() }}">{{ $entry->stato_a->label() }}</span>
            <small class="text-muted ml-2">da {{ $entry->user->name }}</small>
          </div>
          @if($entry->nota)
          <small class="text-muted fst-italic">{{ $entry->nota }}</small>
          @endif
          <small class="d-block text-muted">{{ $entry->created_at->format('d/m/Y H:i') }}</small>
        </div>
      </div>
      @empty
      <p class="text-muted">Nessuna transizione registrata.</p>
      @endforelse
    </div>
    @elseif($tabAttiva === 'checklist')
    <div>
      @forelse($checklistAttivi as $tmpl)
        @php
          $compilata = \App\Models\ChecklistCompilata::where('commessa_id', $commessa->id)
              ->where('checklist_template_id', $tmpl->id)->first();
        @endphp
        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
          <div>
            <strong>{{ $tmpl->nome }}</strong>
            @if($compilata?->isCompletata())
              <span class="badge badge-success ml-2">
                <i class="fas fa-check mr-1"></i>Completata {{ $compilata->completata_at->format('d/m H:i') }}
              </span>
            @elseif($compilata)
              <span class="badge badge-warning ml-2">In corso ({{ $compilata->percentualeCompletamento() }}%)</span>
            @else
              <span class="badge badge-secondary ml-2">Non iniziata</span>
            @endif
          </div>
          @hasanyrole('admin|meccanico')
          <a href="{{ route('officina.checklist', [$commessa->id, $tmpl->id]) }}"
             class="btn btn-sm btn-primary" target="_blank">
            <i class="fas fa-tasks mr-1"></i>
            {{ $compilata ? 'Continua' : 'Inizia' }}
          </a>
          @endhasanyrole
        </div>
      @empty
        <p class="text-muted">Nessun template checklist attivo.</p>
      @endforelse
    </div>
    @elseif($tabAttiva === 'dvi')
    <livewire:dvi.dettaglio-dvi :commessa-id="$commessa->id" :key="'dvi-'.$commessa->id" />
    @elseif($tabAttiva === 'carrozzeria' && $commessa->tipo === \App\Enums\TipoCommessa::Carrozzeria)
    {{-- ── BARRA PROGRESSO FASI CARROZZERIA ──────────────────────────────── --}}
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><i class="fas fa-tasks mr-1 text-muted"></i>Workflow Carrozzeria</h6>
        @if($commessa->stato_carrozzeria && $commessa->stato_carrozzeria->successiva())
        <button wire:click="$set('showConfermaAvanzaFase', true)" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-arrow-right mr-1"></i> Avanza a: {{ $commessa->stato_carrozzeria->successiva()->label() }}
        </button>
        @elseif(!$commessa->stato_carrozzeria)
        <button wire:click="avanzaFaseCarrozzeria" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-play mr-1"></i> Inizia: Accettazione
        </button>
        @endif
      </div>
      <div class="d-flex flex-wrap gap-1" style="gap:.25rem">
        @foreach($fasiCarrozzeria as $fase)
          @php
            $attiva = $commessa->stato_carrozzeria?->value === $fase->value;
            $passata = $commessa->stato_carrozzeria && $commessa->stato_carrozzeria->ordine() > $fase->ordine();
          @endphp
          <span class="badge py-1 px-2 mr-1 {{ $passata ? 'badge-success' : ($attiva ? 'badge-primary' : 'badge-secondary') }}"
            style="font-size:.75rem">
            @if($passata)<i class="fas fa-check mr-1"></i>@endif
            {{ $fase->label() }}
          </span>
        @endforeach
      </div>
    </div>

    {{-- ── PULSANTI AZIONI CARROZZERIA ───────────────────────────────────── --}}
    <div class="d-flex mb-3" style="gap:.5rem">
      <a href="{{ route('pdf.carrozzeria', $commessa->id) }}" class="btn btn-sm btn-outline-danger" target="_blank">
        <i class="fas fa-file-pdf mr-1"></i> PDF Scheda Carrozzeria
      </a>
      @if($commessa->sinistro?->perizia?->importo_netto_liquidato !== null)
      @hasanyrole('admin|cassa')
      <button wire:click="apriDoppiaFattura" class="btn btn-sm btn-warning">
        <i class="fas fa-file-invoice-dollar mr-1"></i> Emetti Doppia Fattura
      </button>
      @endhasanyrole
      @endif
      <button wire:click="$set('showStatoAccettazioneModal', true)" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-map-marker-alt mr-1"></i> Stato Accettazione Veicolo
      </button>
    </div>

    {{-- ── SUB-TAB CARROZZERIA ──────────────────────────────────────────── --}}
    <ul class="nav nav-pills nav-sm mb-3">
      <li class="nav-item">
        <a class="nav-link py-1 {{ $subTabCarrozzeria === 'sinistro' ? 'active' : '' }}"
          wire:click="$set('subTabCarrozzeria', 'sinistro')" href="#">Sinistro & Perizia</a>
      </li>
      <li class="nav-item">
        <a class="nav-link py-1 {{ $subTabCarrozzeria === 'danni' ? 'active' : '' }}"
          wire:click="$set('subTabCarrozzeria', 'danni')" href="#">Danni</a>
      </li>
      <li class="nav-item">
        <a class="nav-link py-1 {{ $subTabCarrozzeria === 'foto' ? 'active' : '' }}"
          wire:click="$set('subTabCarrozzeria', 'foto')" href="#">Foto Danni</a>
      </li>
    </ul>

    @if($subTabCarrozzeria === 'sinistro')
    <livewire:carrozzeria.gestione-sinistro :commessa-id="$commessa->id" :key="'sin-'.$commessa->id" />
    @elseif($subTabCarrozzeria === 'danni')
    <livewire:carrozzeria.gestione-danni :commessa-id="$commessa->id" :key="'danni-'.$commessa->id" />
    @elseif($subTabCarrozzeria === 'foto')
    <livewire:carrozzeria.foto-danni :commessa-id="$commessa->id" :key="'foto-'.$commessa->id" />
    @endif
    @endif
  </div>

  <!-- Widget Marginalità (solo admin/cassa) -->
  @if(!empty($marginalita))
  @hasanyrole('admin|cassa')
  <div class="card card-outline card-secondary mt-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Marginalità</h3>
      <div class="card-tools">
        @php
          $pct = $marginalita['percentuale_margine'] ?? 0;
          $badge = $pct >= 30 ? 'badge-success' : ($pct >= 15 ? 'badge-warning' : ($marginalita['costo_totale'] > 0 ? 'badge-danger' : 'badge-secondary'));
        @endphp
        <span class="badge {{ $badge }} p-2">{{ number_format($pct, 1) }}% margine</span>
      </div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-muted text-uppercase mb-2">Ricavi</h6>
          <table class="table table-sm">
            <tr><td>Manodopera</td><td class="text-right">€ {{ number_format($marginalita['ricavo_manodopera'], 2, ',', '.') }}</td></tr>
            <tr><td>Articoli</td><td class="text-right">€ {{ number_format($marginalita['ricavo_articoli'], 2, ',', '.') }}</td></tr>
            <tr class="font-weight-bold"><td>Totale Ricavi</td><td class="text-right">€ {{ number_format($marginalita['ricavo_totale'], 2, ',', '.') }}</td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <h6 class="text-muted text-uppercase mb-2">Costi</h6>
          <table class="table table-sm">
            <tr><td>Manodopera (ore eff. × costo orario)</td><td class="text-right">€ {{ number_format($marginalita['costo_manodopera'], 2, ',', '.') }}</td></tr>
            <tr><td>Articoli (prezzo acquisto)</td><td class="text-right">€ {{ number_format($marginalita['costo_articoli'], 2, ',', '.') }}</td></tr>
            <tr class="font-weight-bold"><td>Totale Costi</td><td class="text-right">€ {{ number_format($marginalita['costo_totale'], 2, ',', '.') }}</td></tr>
          </table>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 offset-md-6">
          <table class="table table-sm">
            <tr class="font-weight-bold {{ $marginalita['margine_lordo'] >= 0 ? 'text-success' : 'text-danger' }}">
              <td>Margine Lordo</td>
              <td class="text-right">€ {{ number_format($marginalita['margine_lordo'], 2, ',', '.') }}</td>
            </tr>
          </table>
        </div>
      </div>
      <small class="text-muted"><i class="fas fa-lock mr-1"></i>I dati di marginalità sono visibili solo agli utenti autorizzati.</small>
    </div>
  </div>
  @endhasanyrole
  @endif

  {{-- Widget Veicolo di Cortesia --}}
  @hasanyrole('admin|accettatore')
  @php
    $prestitoCortesia = \App\Models\PrestitoCortesia::where('commessa_id', $commessa->id)
      ->whereIn('stato', ['prenotato', 'in_corso'])
      ->with(['veicolo', 'cliente'])
      ->latest()
      ->first();
  @endphp
  <div class="card mt-3">
    <div class="card-header bg-light py-2">
      <h5 class="card-title mb-0"><i class="fas fa-car-side mr-2 text-primary"></i>Veicolo di Cortesia</h5>
    </div>
    <div class="card-body py-2">
      @if($prestitoCortesia)
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <strong>{{ $prestitoCortesia->veicolo->targa }}</strong>
            {{ $prestitoCortesia->veicolo->marca }} {{ $prestitoCortesia->veicolo->modello }}
            &bull;
            <span class="badge badge-{{ $prestitoCortesia->stato->badgeClass() }}">{{ $prestitoCortesia->stato->label() }}</span>
            &bull; Rientro previsto: <strong>{{ $prestitoCortesia->data_rientro_prevista->format('d/m/Y') }}</strong>
            @if($prestitoCortesia->isInRitardo())
              <span class="badge badge-danger ml-1">IN RITARDO</span>
            @endif
          </div>
          <div>
            <a href="{{ route('cortesia.rientro', $prestitoCortesia->id) }}" class="btn btn-sm btn-warning">
              <i class="fas fa-undo mr-1"></i> Registra rientro
            </a>
            <a href="{{ route('cortesia.contratto', $prestitoCortesia->id) }}" class="btn btn-sm btn-outline-secondary ml-1" target="_blank">
              <i class="fas fa-file-pdf"></i> Contratto
            </a>
          </div>
        </div>
      @else
        <div class="d-flex align-items-center justify-content-between">
          <span class="text-muted">Nessun veicolo di cortesia assegnato a questa commessa.</span>
          <a href="{{ route('cortesia.consegna.commessa', $commessa->id) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus mr-1"></i> Assegna veicolo di cortesia
          </a>
        </div>
      @endif
    </div>
  </div>
  @endhasanyrole

  <!-- Modal Transizione Stato -->
  @if($showTransizioneModal && $statoTarget)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            Transizione a: <span class="badge {{ \App\Enums\StatoCommessa::from($statoTarget)->badgeClass() }}">
              {{ \App\Enums\StatoCommessa::from($statoTarget)->label() }}
            </span>
          </h5>
          <button type="button" class="close" wire:click="$set('showTransizioneModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>
              Nota
              @if($statoTarget === 'sospesa') <span class="text-danger">*</span> @endif
            </label>
            <textarea wire:model="notaTransizione" class="form-control @error('notaTransizione') is-invalid @enderror"
              rows="3"
              placeholder="{{ $statoTarget === 'sospesa' ? 'Motivo della sospensione (obbligatorio)...' : 'Note opzionali...' }}"></textarea>
            @error('notaTransizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showTransizioneModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="eseguiTransizione" wire:loading.attr="disabled">
            <span wire:loading wire:target="eseguiTransizione" class="spinner-border spinner-border-sm mr-1"></span>
            Conferma
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Modal Firma Digitale -->
  @if($showFirmaModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            Firma {{ $tipoFirma === 'consegna' ? 'di Consegna' : 'di Accettazione' }}
          </h5>
          <button type="button" class="close" wire:click="$set('showFirmaModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body"
          x-data="{
            pad: null,
            init() {
              this.pad = new SignaturePad(this.$refs.canvas, { backgroundColor: 'rgb(255,255,255)' });
              this.resizeCanvas();
            },
            resizeCanvas() {
              const ratio = Math.max(window.devicePixelRatio || 1, 1);
              const canvas = this.$refs.canvas;
              canvas.width = canvas.offsetWidth * ratio;
              canvas.height = canvas.offsetHeight * ratio;
              canvas.getContext('2d').scale(ratio, ratio);
              this.pad.clear();
            },
            clear() {
              this.pad.clear();
              $wire.set('firmasvg', '');
            },
            salva() {
              if (this.pad.isEmpty()) {
                alert('Disegna la firma prima di salvare.');
                return;
              }
              const svg = this.pad.toSVG();
              $wire.set('firmasvg', svg);
              $wire.salvaFirma();
            }
          }">
          <p class="text-muted mb-2">
            Il cliente {{ $commessa->cliente->nome_completo }} firma per
            {{ $tipoFirma === 'consegna' ? 'presa visione e ritiro del veicolo' : 'accettazione del preventivo/lavori' }}.
          </p>
          @error('firmasvg')<div class="alert alert-danger py-1">{{ $message }}</div>@enderror
          <div class="border rounded bg-white" style="touch-action:none">
            <canvas x-ref="canvas" style="width:100%;height:250px;display:block"></canvas>
          </div>
          <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" x-on:click="clear()">
              <i class="fas fa-eraser"></i> Cancella
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showFirmaModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" x-on:click="salva()">
            <i class="fas fa-signature"></i> Salva Firma e Continua
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal Conferma Avanzamento Fase Carrozzeria --}}
  @if($showConfermaAvanzaFase)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Avanza Fase Carrozzeria</h5>
          <button type="button" class="close" wire:click="$set('showConfermaAvanzaFase', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <p>Avanzare alla fase: <strong>{{ $commessa->stato_carrozzeria?->successiva()?->label() }}</strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showConfermaAvanzaFase', false)">Annulla</button>
          <button type="button" class="btn btn-primary btn-sm" wire:click="avanzaFaseCarrozzeria">Conferma</button>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal Doppia Fattura --}}
  @if($showDoppiaFatturaModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-invoice-dollar text-warning mr-2"></i>Emetti Doppia Fattura</h5>
          <button type="button" class="close" wire:click="$set('showDoppiaFatturaModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info py-2">
            <small>Sinistro: <strong>{{ $previewDoppiaFattura['sinistro_numero'] ?? '—' }}</strong>
            — Compagnia: <strong>{{ $previewDoppiaFattura['compagnia'] ?? '—' }}</strong></small>
          </div>
          <table class="table table-sm">
            <tr>
              <td>Totale Commessa</td>
              <td class="text-right font-weight-bold">€ {{ number_format($previewDoppiaFattura['totale_commessa'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="table-warning">
              <td><i class="fas fa-building mr-1"></i>Fattura Assicurazione (imponibile)</td>
              <td class="text-right">€ {{ number_format($previewDoppiaFattura['imp_assicurazione'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="table-warning">
              <td>&nbsp;&nbsp;IVA Assicurazione</td>
              <td class="text-right">€ {{ number_format($previewDoppiaFattura['iva_assicurazione'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="table-warning font-weight-bold">
              <td>&nbsp;&nbsp;Totale Assicurazione</td>
              <td class="text-right">€ {{ number_format($previewDoppiaFattura['tot_assicurazione'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="table-light">
              <td><i class="fas fa-user mr-1"></i>Fattura Cliente (imponibile)</td>
              <td class="text-right">€ {{ number_format($previewDoppiaFattura['imp_cliente'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="table-light font-weight-bold">
              <td>&nbsp;&nbsp;Totale Cliente</td>
              <td class="text-right">€ {{ number_format($previewDoppiaFattura['tot_cliente'] ?? 0, 2, ',', '.') }}</td>
            </tr>
          </table>
          <p class="text-muted small">Verranno create due fatture in bozza collegate tra loro.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showDoppiaFatturaModal', false)">Annulla</button>
          <button type="button" class="btn btn-warning" wire:click="generaDoppiaFattura" wire:loading.attr="disabled">
            <span wire:loading wire:target="generaDoppiaFattura" class="spinner-border spinner-border-sm mr-1"></span>
            <i class="fas fa-file-invoice-dollar mr-1"></i>Genera Fatture
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal Stato Accettazione Veicolo --}}
  @if($showStatoAccettazioneModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Stato Veicolo all'Accettazione</h5>
          <button type="button" class="close" wire:click="$set('showStatoAccettazioneModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Clicca sulla zona del veicolo per segnare danni preesistenti al momento dell'accettazione.</p>
          @php
            $zoneAccettazione = \App\Enums\ZonaDanno::cases();
          @endphp
          <div class="row">
            @foreach($zoneAccettazione as $zona)
            <div class="col-md-4 mb-2">
              <div class="card card-sm {{ isset($noteAccettazione[$zona->value]) ? 'border-warning' : '' }}"
                style="cursor:pointer" wire:click="toggleZonaAccettazione('{{ $zona->value }}')">
                <div class="card-body p-2">
                  <div class="d-flex align-items-center">
                    <i class="fas {{ isset($noteAccettazione[$zona->value]) ? 'fa-exclamation-triangle text-warning' : 'fa-check-circle text-success' }} mr-2"></i>
                    <span class="small">{{ $zona->label() }}</span>
                  </div>
                  @if(isset($noteAccettazione[$zona->value]))
                  <input type="text" wire:model.live="noteAccettazione.{{ $zona->value }}.nota"
                    class="form-control form-control-sm mt-1"
                    placeholder="Note sul danno preesistente..."
                    wire:click.stop>
                  @endif
                </div>
              </div>
            </div>
            @endforeach
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showStatoAccettazioneModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salvaStatoAccettazione">
            <i class="fas fa-save mr-1"></i>Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Modal scadenze suggerite --}}
  @if($showScadenzeSuggerite)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-calendar-check text-success mr-2"></i>Scadenze suggerite
          </h5>
        </div>
        <div class="modal-body">
          <p>Dalla commessa sono state rilevate le seguenti scadenze. Selezionare quelle da creare:</p>
          @foreach($suggerimentiScadenze as $i => $s)
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="sug_dc_{{ $i }}"
              wire:model="tipiScadenzeSelezionati" value="{{ $s['tipo'] }}">
            <label class="custom-control-label" for="sug_dc_{{ $i }}">
              <strong>{{ \App\Enums\TipoScadenza::from($s['tipo'])->label() }}</strong>
              — {{ $s['descrizione'] }}
              <small class="text-muted d-block">
                Scadenza: {{ \Carbon\Carbon::parse($s['data_scadenza'])->format('d/m/Y') }}
                @if($s['km_scadenza']) · KM: {{ number_format($s['km_scadenza']) }} @endif
              </small>
            </label>
          </div>
          @endforeach
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            wire:click="$set('showScadenzeSuggerite', false)">Ignora</button>
          <button type="button" class="btn btn-success"
            wire:click="confermaSuggerimentiScadenze">
            <i class="fas fa-check mr-1"></i>Crea scadenze selezionate
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

</div>

@push('scripts')
<script>
  // Inizializza la firma pad quando il canvas è visibile
  document.addEventListener('livewire:navigated', () => {
    if (window.Alpine) window.Alpine.initTree(document.body);
  });
</script>
@endpush
