<div>
    {{-- Breadcrumb / Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-car-side mr-2"></i>
                        Accettazione veicolo
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Accettazione</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            {{-- Indicatore stadi --}}
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <span class="badge badge-{{ $stadio >= 1 ? 'primary' : 'secondary' }} mr-1 py-2 px-3">1. Targa</span>
                        <i class="fas fa-chevron-right text-muted mx-1"></i>
                        <span class="badge badge-{{ $stadio >= 2 ? 'primary' : 'secondary' }} mr-1 py-2 px-3">2. Veicolo</span>
                        <i class="fas fa-chevron-right text-muted mx-1"></i>
                        <span class="badge badge-{{ $stadio >= 3 ? 'primary' : 'secondary' }} py-2 px-3">3. Ordine di lavoro</span>
                    </div>
                </div>
            </div>

            @if($erroreGenerale)
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ $erroreGenerale }}
                </div>
            @endif

            {{-- ================================================== --}}
            {{-- STADIO 1 — Ricerca targa                           --}}
            {{-- ================================================== --}}
            @if($stadio === 1)
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Inserisci targa</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="targaInput">Targa veicolo</label>
                                <input
                                    type="text"
                                    id="targaInput"
                                    wire:model.live.debounce.300ms="targaInput"
                                    class="form-control form-control-lg text-uppercase"
                                    placeholder="Es. AB123CD"
                                    autofocus
                                    autocomplete="off"
                                    style="letter-spacing: 4px; font-size: 1.6rem; font-weight: bold;"
                                    wire:keydown.enter="$dispatch('targaEnterPressed')"
                                >
                            </div>

                            {{-- Match parziali --}}
                            @if(count($matchParziali) > 0)
                                <div class="list-group mt-2">
                                    @foreach($matchParziali as $match)
                                        <button
                                            type="button"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                            wire:click="selezionaVeicoloEsistente({{ $match['id'] }})"
                                        >
                                            <div>
                                                <strong class="text-primary">{{ $match['targa'] }}</strong>
                                                <span class="text-muted ml-2">{{ $match['desc'] }}</span>
                                            </div>
                                            <span class="text-muted small">{{ $match['cliente'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Nessun veicolo trovato --}}
                            @if(strlen(trim($targaInput)) >= 2 && count($matchParziali) === 0)
                                <div class="callout callout-warning mt-3">
                                    <p>Nessun veicolo con targa "<strong>{{ strtoupper(trim($targaInput)) }}</strong>".</p>
                                    <button
                                        type="button"
                                        class="btn btn-warning"
                                        wire:click="proseguiNuovoVeicolo"
                                    >
                                        <i class="fas fa-plus mr-1"></i> Crea nuovo veicolo
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ================================================== --}}
            {{-- STADIO 2A — Veicolo esistente                      --}}
            {{-- ================================================== --}}
            @if($stadio === 2 && !$modoNuovoVeicolo && $veicoloCorrente)
            <div class="row">
                {{-- Colonna sinistra --}}
                <div class="col-md-8">

                    {{-- Card veicolo --}}
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-car mr-1"></i>
                                {{ $veicoloCorrente->targa }} — {{ $veicoloCorrente->descrizione }}
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" wire:click="tornaAlloStadio(1)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr><th style="width:120px">Marca</th><td>{{ $veicoloCorrente->marca ?? '—' }}</td></tr>
                                        <tr><th>Modello</th><td>{{ $veicoloCorrente->modello ?? '—' }}</td></tr>
                                        <tr><th>Anno</th><td>{{ $veicoloCorrente->anno_immatricolazione ?? '—' }}</td></tr>
                                        <tr><th>VIN</th><td class="text-monospace small">{{ $veicoloCorrente->vin ?? '—' }}</td></tr>
                                        <tr><th>Alimentazione</th><td>{{ $veicoloCorrente->alimentazione?->value ?? '—' }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Km attuali</strong></label>
                                        <input
                                            type="number"
                                            wire:model.blur="km_attuali"
                                            class="form-control @error('km_attuali') is-invalid @enderror"
                                            min="0"
                                            placeholder="Inserisci km"
                                        >
                                        @error('km_attuali')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if($veicoloCorrente->km_attuali)
                                            <small class="text-muted">Ultimo valore: {{ number_format($veicoloCorrente->km_attuali, 0, ',', '.') }} km</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card cliente --}}
                    @if($clienteCorrente)
                    <div class="card card-secondary">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-1"></i> Cliente</h3></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>{{ $clienteCorrente->nome_completo }}</strong><br>
                                    @if($clienteCorrente->telefono) <i class="fas fa-phone mr-1 text-muted"></i> {{ $clienteCorrente->telefono }}<br> @endif
                                    @if($clienteCorrente->email) <i class="fas fa-envelope mr-1 text-muted"></i> {{ $clienteCorrente->email }} @endif
                                </div>
                                <div class="col-md-6">
                                    @if($clienteCorrente->codice_fiscale) <small class="text-muted">CF: {{ $clienteCorrente->codice_fiscale }}</small><br> @endif
                                    @if($clienteCorrente->partita_iva) <small class="text-muted">P.IVA: {{ $clienteCorrente->partita_iva }}</small> @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Timeline ultimi interventi --}}
                    @if($ultimiInterventi->isNotEmpty())
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history mr-1"></i> Ultimi interventi</h3>
                            <div class="card-tools">
                                <a href="{{ route('veicoli.show', $veicoloCorrente->id) }}" class="btn btn-sm btn-outline-secondary">
                                    Storico completo <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="timeline timeline-inverse p-3">
                                @foreach($ultimiInterventi as $intervento)
                                <div>
                                    <i class="fas fa-wrench bg-{{ $intervento->stato->value === 'fatturata' ? 'success' : ($intervento->stato->value === 'completata' ? 'info' : 'secondary') }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            <i class="fas fa-clock"></i>
                                            {{ $intervento->data_ingresso?->format('d/m/Y') ?? '—' }}
                                        </span>
                                        <h3 class="timeline-header">
                                            <a href="{{ route('commesse.show', $intervento->id) }}">{{ $intervento->numero }}</a>
                                            <span class="badge badge-secondary ml-1">{{ $intervento->stato->label() ?? $intervento->stato->value }}</span>
                                        </h3>
                                        <div class="timeline-body text-muted small">
                                            {{ \Illuminate\Support\Str::limit($intervento->descrizione_cliente, 100) }}
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <div><i class="fas fa-clock bg-gray"></i></div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="callout callout-info">
                        <i class="fas fa-info-circle mr-1"></i> Nessun intervento precedente per questo veicolo.
                    </div>
                    @endif

                </div>

                {{-- Colonna destra: pulsante avanzamento --}}
                <div class="col-md-4">
                    <div class="card card-primary sticky-top" style="top:80px">
                        <div class="card-header"><h3 class="card-title">Prossimo passo</h3></div>
                        <div class="card-body">
                            <p class="text-muted">Verifica i km e procedi all'apertura dell'ordine di lavoro.</p>
                            <button
                                type="button"
                                class="btn btn-primary btn-block"
                                wire:click="avanzaAlloStadio3"
                            >
                                Apri ordine di lavoro <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                            <button type="button" class="btn btn-link btn-block mt-1" wire:click="tornaAlloStadio(1)">
                                <i class="fas fa-arrow-left mr-1"></i> Cambia targa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ================================================== --}}
            {{-- STADIO 2B — Nuovo veicolo                          --}}
            {{-- ================================================== --}}
            @if($stadio === 2 && $modoNuovoVeicolo)
            <div class="row">
                <div class="col-md-8">

                    {{-- Form veicolo --}}
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-car-side mr-1"></i> Nuovo veicolo — {{ strtoupper(trim($targaInput)) }}</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" wire:click="tornaAlloStadio(1)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">

                            @if($plateLookupEnabled)
                            <div class="d-flex align-items-center mb-3">
                                <button
                                    type="button"
                                    class="btn btn-outline-info btn-sm"
                                    wire:click="cercaTarga"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading wire:target="cercaTarga"><i class="fas fa-spinner fa-spin mr-1"></i></span>
                                    <span wire:loading.remove wire:target="cercaTarga"><i class="fas fa-search mr-1"></i></span>
                                    Recupera dati targa
                                </button>
                                @if($lookupMessaggio)
                                    <span class="ml-2 text-warning small">{{ $lookupMessaggio }}</span>
                                @endif
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tipo *</label>
                                        <select wire:model="nv_tipo" class="form-control @error('nv_tipo') is-invalid @enderror">
                                            <option value="auto">Auto</option>
                                            <option value="moto">Moto</option>
                                        </select>
                                        @error('nv_tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Marca *</label>
                                        <input type="text" wire:model="nv_marca" class="form-control @error('nv_marca') is-invalid @enderror" placeholder="Es. Volkswagen">
                                        @error('nv_marca') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Modello *</label>
                                        <input type="text" wire:model="nv_modello" class="form-control @error('nv_modello') is-invalid @enderror" placeholder="Es. Golf">
                                        @error('nv_modello') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Anno</label>
                                        <input type="number" wire:model="nv_anno" class="form-control" min="1900" max="2100" placeholder="2020">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Alimentazione</label>
                                        <select wire:model="nv_alimentazione" class="form-control">
                                            <option value="benzina">Benzina</option>
                                            <option value="diesel">Diesel</option>
                                            <option value="ibrido">Ibrido</option>
                                            <option value="elettrico">Elettrico</option>
                                            <option value="gpl">GPL</option>
                                            <option value="metano">Metano</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Km attuali</label>
                                        <input type="number" wire:model="nv_km" class="form-control" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>VIN / Telaio</label>
                                        <input type="text" wire:model="nv_vin" class="form-control text-monospace" maxlength="17" placeholder="17 caratteri">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cliente --}}
                    <div class="card card-secondary">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-1"></i> Cliente</h3></div>
                        <div class="card-body">

                            @if(! $clienteSelezionatoId && ! $modoNuovoCliente)
                            <div class="form-group">
                                <label>Cerca cliente esistente</label>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="searchCliente"
                                    class="form-control @error('clienteSelezionatoId') is-invalid @enderror"
                                    placeholder="Nome, cognome, telefono…"
                                    autocomplete="off"
                                >
                                @error('clienteSelezionatoId') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                @if(count($suggerimentiClienti) > 0)
                                    <div class="list-group mt-1">
                                        @foreach($suggerimentiClienti as $sc)
                                            <button type="button" class="list-group-item list-group-item-action" wire:click="selezionaCliente({{ $sc['id'] }})">
                                                <strong>{{ $sc['label'] }}</strong>
                                                @if($sc['telefono']) <span class="text-muted ml-2">{{ $sc['telefono'] }}</span> @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="text-center my-2 text-muted">— oppure —</div>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('modoNuovoCliente', true)">
                                <i class="fas fa-user-plus mr-1"></i> Crea nuovo cliente
                            </button>
                            @endif

                            @if($clienteSelezionatoId)
                            <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                                <span><i class="fas fa-check-circle mr-1"></i> Cliente selezionato: <strong>{{ $searchCliente }}</strong></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetCliente">Cambia</button>
                            </div>
                            @endif

                            @if($modoNuovoCliente)
                            <div>
                                <div class="form-group">
                                    <label>Tipo cliente *</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" wire:model="nc_tipo" value="fisica" id="ncFisica">
                                            <label class="form-check-label" for="ncFisica">Persona fisica</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" wire:model="nc_tipo" value="giuridica" id="ncGiuridica">
                                            <label class="form-check-label" for="ncGiuridica">Azienda</label>
                                        </div>
                                    </div>
                                </div>

                                @if($nc_tipo === 'fisica')
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Nome *</label>
                                            <input type="text" wire:model="nc_nome" class="form-control @error('nc_nome') is-invalid @enderror">
                                            @error('nc_nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Cognome *</label>
                                            <input type="text" wire:model="nc_cognome" class="form-control @error('nc_cognome') is-invalid @enderror">
                                            @error('nc_cognome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="form-group">
                                    <label>Ragione sociale *</label>
                                    <input type="text" wire:model="nc_ragione_sociale" class="form-control @error('nc_ragione_sociale') is-invalid @enderror">
                                    @error('nc_ragione_sociale') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Telefono</label>
                                            <input type="text" wire:model="nc_telefono" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" wire:model="nc_email" class="form-control @error('nc_email') is-invalid @enderror">
                                            @error('nc_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Codice Fiscale</label>
                                            <input type="text" wire:model="nc_codice_fiscale" class="form-control text-uppercase" maxlength="16">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Partita IVA</label>
                                            <input type="text" wire:model="nc_partita_iva" class="form-control" maxlength="11">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-link btn-sm p-0" wire:click="$set('modoNuovoCliente', false)">
                                    <i class="fas fa-arrow-left mr-1"></i> Torna alla ricerca
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Colonna destra --}}
                <div class="col-md-4">
                    <div class="card card-primary sticky-top" style="top:80px">
                        <div class="card-header"><h3 class="card-title">Prossimo passo</h3></div>
                        <div class="card-body">
                            <button type="button" class="btn btn-primary btn-block" wire:click="avanzaAlloStadio3">
                                Apri ordine di lavoro <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                            <button type="button" class="btn btn-link btn-block mt-1" wire:click="tornaAlloStadio(1)">
                                <i class="fas fa-arrow-left mr-1"></i> Cambia targa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ================================================== --}}
            {{-- STADIO 3 — Apertura OdL                            --}}
            {{-- ================================================== --}}
            @if($stadio === 3)
            <div class="row">
                <div class="col-md-8">

                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Ordine di lavoro</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" wire:click="tornaAlloStadio(2)">
                                    <i class="fas fa-arrow-left"></i> Indietro
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tipo intervento *</label>
                                        <select wire:model="tipo" class="form-control @error('tipo') is-invalid @enderror">
                                            <option value="meccanica">Meccanica</option>
                                            <option value="tagliando">Tagliando</option>
                                            <option value="carrozzeria">Carrozzeria</option>
                                        </select>
                                        @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Consegna prevista</label>
                                        <input type="date" wire:model="data_uscita_prevista" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Richieste del cliente *</label>
                                <textarea
                                    wire:model="descrizione_cliente"
                                    class="form-control @error('descrizione_cliente') is-invalid @enderror"
                                    rows="4"
                                    placeholder="Descrivi la richiesta del cliente…"
                                    autofocus
                                ></textarea>
                                @error('descrizione_cliente') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Pacchetti (se abilitati) --}}
                            @if($packagesEnabled)
                            <div class="form-group">
                                <label><i class="fas fa-box mr-1"></i> Pacchetto servizio (opzionale)</label>
                                @if(! $pacchettoId)
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="cercaPacchetto"
                                    class="form-control"
                                    placeholder="Cerca pacchetto…"
                                    autocomplete="off"
                                >
                                @if(count($suggerimentiPacchetti) > 0)
                                    <div class="list-group mt-1">
                                        @foreach($suggerimentiPacchetti as $p)
                                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between" wire:click="selezionaPacchetto({{ $p['id'] }})">
                                                <span>{{ $p['nome'] }} <small class="text-muted">({{ $p['righe_count'] }} righe)</small></span>
                                                <span class="text-primary font-weight-bold">€ {{ number_format($p['totale'], 2, ',', '.') }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                                @else
                                <div class="alert alert-info d-flex justify-content-between align-items-center mb-0 mt-1">
                                    <span><i class="fas fa-box-open mr-1"></i> {{ $cercaPacchetto }} — <strong>€ {{ number_format($totalePacchetto, 2, ',', '.') }}</strong></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetPacchetto">Rimuovi</button>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Riepilogo sticky --}}
                <div class="col-md-4">
                    <div class="card card-success sticky-top" style="top:80px">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i> Riepilogo</h3></div>
                        <div class="card-body">
                            {{-- Veicolo --}}
                            <p class="mb-1"><strong>Targa:</strong> {{ strtoupper(trim($targaInput)) }}</p>
                            @if($modoNuovoVeicolo)
                                <p class="mb-1"><strong>Veicolo:</strong> {{ $nv_marca }} {{ $nv_modello }}</p>
                            @elseif($veicoloCorrente)
                                <p class="mb-1"><strong>Veicolo:</strong> {{ $veicoloCorrente->descrizione }}</p>
                            @endif

                            {{-- Cliente --}}
                            @if($clienteCorrente)
                                <p class="mb-1"><strong>Cliente:</strong> {{ $clienteCorrente->nome_completo }}</p>
                            @elseif($clienteSelezionatoId)
                                <p class="mb-1"><strong>Cliente:</strong> {{ $searchCliente }}</p>
                            @elseif($modoNuovoCliente)
                                <p class="mb-1"><strong>Cliente:</strong>
                                    @if($nc_tipo === 'fisica') {{ trim(($nc_nome ?? '') . ' ' . ($nc_cognome ?? '')) ?: '(nuovo)' }}
                                    @else {{ $nc_ragione_sociale ?: '(nuovo)' }}
                                    @endif
                                </p>
                            @endif

                            @if($modoNuovoVeicolo && $nv_km !== null)
                                <p class="mb-1"><strong>Km:</strong> {{ number_format($nv_km, 0, ',', '.') }}</p>
                            @elseif(! $modoNuovoVeicolo && $km_attuali !== null)
                                <p class="mb-1"><strong>Km:</strong> {{ number_format($km_attuali, 0, ',', '.') }}</p>
                            @endif

                            @if($pacchettoId)
                                <p class="mb-1"><strong>Pacchetto:</strong> {{ $cercaPacchetto }}</p>
                                <p class="mb-3"><strong>Totale stimato:</strong> <span class="text-primary font-weight-bold">€ {{ number_format($totalePacchetto, 2, ',', '.') }}</span></p>
                            @else
                                <div class="mb-3"></div>
                            @endif

                            <button
                                type="button"
                                class="btn btn-success btn-block"
                                wire:click="apriOdl(false)"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading wire:target="apriOdl"><i class="fas fa-spinner fa-spin mr-1"></i></span>
                                <i class="fas fa-check mr-1"></i> Apri OdL
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-success btn-block mt-2"
                                wire:click="apriOdl(true)"
                                wire:loading.attr="disabled"
                            >
                                <i class="fas fa-print mr-1"></i> Apri OdL e stampa scheda
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </section>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('apriStampaScheda', ({ url }) => {
            window.open(url, '_blank');
        });
    });
</script>
@endpush
