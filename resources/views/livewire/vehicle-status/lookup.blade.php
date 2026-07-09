<div x-data="{ expanded: {{ $autoExpandId ? $autoExpandId : 'null' }} }">

    {{-- Search bar --}}
    <div class="row mb-3">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="input-group input-group-lg">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-primary text-white">
                        <i class="fas fa-phone"></i>
                    </span>
                </div>
                <input
                    type="text"
                    class="form-control form-control-lg"
                    placeholder="Targa, cognome o telefono (min. 2 caratteri)…"
                    wire:model.live.debounce.200ms="ricerca"
                    autofocus
                    autocomplete="off"
                    style="font-size: 1.4rem;"
                >
            </div>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading wire:target="ricerca" class="mb-2">
        <span class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i> Ricerca…</span>
    </div>

    {{-- Min chars hint --}}
    @if(mb_strlen(trim($ricerca)) > 0 && mb_strlen(trim($ricerca)) < 2)
        <div class="alert alert-light border py-2">
            <i class="fas fa-info-circle text-muted mr-1"></i>
            Digita almeno 2 caratteri per cercare.
        </div>
    @endif

    {{-- No results --}}
    @if(mb_strlen(trim($ricerca)) >= 2 && $results->isEmpty())
        <div class="alert alert-warning">
            <i class="fas fa-search mr-1"></i>
            Nessun veicolo trovato per <strong>{{ $ricerca }}</strong>.
        </div>
    @endif

    {{-- Result cards --}}
    @foreach($results as $card)
    <div
        class="card card-outline mb-3"
        :class="{
            'card-primary':  expanded === {{ $card->veicoloId }},
            'card-default':  expanded !== {{ $card->veicoloId }},
        }"
        style="cursor:pointer;"
        @click="expanded = (expanded === {{ $card->veicoloId }}) ? null : {{ $card->veicoloId }}"
    >
        {{-- Card header: targa + nome cliente --}}
        <div class="card-header py-2" style="border-bottom: none;">
            <div class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
                {{-- Targa --}}
                <span class="badge badge-dark px-3 py-2" style="font-size: 1.4rem; letter-spacing: .08em; font-family: monospace;">
                    {{ $card->targa }}
                </span>
                {{-- Marca + Modello --}}
                <span style="font-size: 1.25rem; font-weight: 600;">
                    {{ $card->marca }} {{ $card->modello }}
                </span>
                {{-- Cliente --}}
                <span class="text-muted" style="font-size: 1.1rem;">
                    <i class="fas fa-user mr-1"></i>{{ $card->clienteNome }}
                    @if($card->clienteTelefono)
                        <span class="ml-2 text-secondary" style="font-size: 1rem;">
                            <i class="fas fa-phone-alt mr-1"></i>{{ $card->clienteTelefono }}
                        </span>
                    @endif
                </span>
                {{-- Chevron --}}
                <span class="ml-auto text-muted">
                    <i class="fas" :class="expanded === {{ $card->veicoloId }} ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </span>
            </div>
        </div>

        {{-- Card body --}}
        <div class="card-body pt-0 pb-2" x-show="expanded === {{ $card->veicoloId }}" x-cloak>

            {{-- Riga 2: stato OdL + consegna --}}
            @if($card->commessaId)
            <div class="d-flex align-items-center flex-wrap mb-2" style="gap: .5rem;">
                <span class="badge {{ $card->commessaStato?->badgeClass() }} px-2 py-1" style="font-size: 1rem;">
                    {{ $card->commessaStato?->label() }}
                </span>
                <span class="text-secondary" style="font-size: .95rem;">
                    OdL <strong>{{ $card->commessaNumero }}</strong>
                    @if($card->dataIngresso)
                        · entrato il {{ $card->dataIngresso->format('d/m/Y') }}
                    @endif
                </span>
                @if($card->consegnaPrevista)
                    <span class="badge badge-info px-2 py-1" style="font-size: .95rem;">
                        <i class="far fa-clock mr-1"></i>
                        Consegna prevista: {{ $card->consegnaPrevista->format('d/m/Y H:i') }}
                    </span>
                @endif
            </div>

            {{-- Riga 3: semaforo + approvazione + comunicazione --}}
            <div class="d-flex align-items-start flex-wrap" style="gap: .75rem;">

                {{-- Semaforo ricambi --}}
                @if($card->semaforoRicambi === 'verde')
                    <span class="badge badge-success px-2 py-1" style="font-size: .9rem;">
                        <i class="fas fa-circle mr-1"></i> Ricambi OK
                    </span>
                @elseif($card->semaforoRicambi === 'giallo')
                    <div>
                        <span class="badge badge-warning text-dark px-2 py-1" style="font-size: .9rem;">
                            <i class="fas fa-circle mr-1"></i> Ricambi mancanti
                        </span>
                        @if(!empty($card->ricambiMancanti))
                        <ul class="mb-0 mt-1 pl-3" style="font-size: .85rem;">
                            @foreach($card->ricambiMancanti as $m)
                                <li>
                                    {{ $m['descrizione'] }}
                                    @if(!empty($m['statoOrdine']))
                                        <span class="text-muted">
                                            — ordinato ({{ $m['statoOrdine'] }}
                                            @if(!empty($m['dataOrdinePrevista']))
                                                , previsto {{ $m['dataOrdinePrevista'] }}
                                            @endif
                                            )
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                @else
                    <span class="badge badge-secondary px-2 py-1" style="font-size: .9rem;">
                        <i class="fas fa-circle mr-1"></i> Nessun ricambio
                    </span>
                @endif

                {{-- DVI approvazione (Step 10) --}}
                @if($card->dviStato)
                    <span class="badge badge-light border px-2 py-1" style="font-size: .9rem;">
                        <i class="fas fa-clipboard-check mr-1"></i>
                        Preventivo: {{ $card->dviStato }}
                        @if($card->dviData)
                            <span class="text-muted"> ({{ $card->dviData->format('d/m') }})</span>
                        @endif
                    </span>
                @endif

                {{-- Ultima comunicazione (Step 30) --}}
                @if($card->comunicazioneCanale)
                    <span class="badge badge-light border px-2 py-1" style="font-size: .9rem;" title="{{ $card->comunicazioneEstratto }}">
                        <i class="fas fa-comment-alt mr-1"></i>
                        {{ $card->comunicazioneCanale }}
                        @if($card->comunicazioneData)
                            · {{ $card->comunicazioneData->format('d/m H:i') }}
                        @endif
                        @if($card->comunicazioneEstratto)
                            <span class="text-muted d-none d-md-inline ml-1">"{{ Str::limit($card->comunicazioneEstratto, 40) }}"</span>
                        @endif
                    </span>
                @endif

            </div>

            @else
            {{-- Nessun OdL attivo --}}
            <div class="text-muted" style="font-size: 1rem;">
                <i class="fas fa-check-circle text-success mr-1"></i>
                @if($card->ultimaConsegnaLabel)
                    {{ $card->ultimaConsegnaLabel }}
                @else
                    Nessuna commessa attiva.
                @endif
            </div>
            @endif

            {{-- Azioni --}}
            @if($card->commessaId)
            <div class="mt-3 d-flex" style="gap: .5rem;">
                <a href="{{ route('commesse.show', $card->commessaId) }}"
                   class="btn btn-primary btn-sm"
                   @click.stop>
                    <i class="fas fa-external-link-alt mr-1"></i> Apri OdL
                </a>
                @if($card->comunicazioneCanale !== null || config('features.communications', true))
                {{-- "Annota chiamata" presente solo se Step 30 attivo (communications route esiste) --}}
                @if(\Illuminate\Support\Facades\Schema::hasTable('communications'))
                <a href="{{ route('commesse.show', $card->commessaId) }}#comunicazioni"
                   class="btn btn-outline-secondary btn-sm"
                   @click.stop>
                    <i class="fas fa-phone-alt mr-1"></i> Annota chiamata
                </a>
                @endif
                @endif
            </div>
            @endif
        </div>
    </div>
    @endforeach

    {{-- Raffinare hint --}}
    @if($results->count() >= 5)
        <div class="text-center text-muted small mt-2">
            <i class="fas fa-info-circle mr-1"></i>
            Mostrati i primi 5 risultati — raffina la ricerca per trovare il veicolo.
        </div>
    @endif

</div>
