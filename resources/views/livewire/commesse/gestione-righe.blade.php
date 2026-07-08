<div>
  <div class="d-flex justify-content-between mb-3">
    <div>
      <h6 class="mb-0">Righe di Lavorazione</h6>
    </div>
    @can('update', $commessa)
    <div class="d-flex gap-2">
      <button wire:click="apriPacchettoModal()" class="btn btn-sm btn-outline-success">
        <i class="fas fa-box-open"></i> Applica pacchetto
      </button>
      <button wire:click="apriModal()" class="btn btn-sm btn-primary">
        <i class="fas fa-plus"></i> Aggiungi riga
      </button>
    </div>
    @endcan
  </div>

  <div wire:sortable="aggiornaOrdinamento">
    @forelse($righe as $riga)
    <div wire:sortable.item="{{ $riga->id }}" wire:key="riga-{{ $riga->id }}"
      class="card card-body py-2 px-3 mb-2 shadow-sm">
      <div class="row align-items-center">
        @can('update', $commessa)
        <div class="col-auto" wire:sortable.handle style="cursor:grab">
          <i class="fas fa-grip-vertical text-muted"></i>
        </div>
        @endcan
        <div class="col-md-1">
          <span class="badge badge-{{ $riga->tipo->value === 'manodopera' ? 'primary' : ($riga->tipo->value === 'nota' ? 'light text-dark' : 'secondary') }}">
            {{ $riga->tipo->label() }}
          </span>
          @if($riga->pacchetto_servizio_id)
          <br><small class="text-muted" title="Da pacchetto"><i class="fas fa-box-open"></i></small>
          @endif
        </div>
        <div class="col-md-5">
          {{ $riga->descrizione }}
          @if($riga->articolo)
          <small class="text-muted ml-1">[{{ $riga->articolo->codice }}]</small>
          @endif
          @if($riga->tipo->value === 'articolo' && $prezziSuggeriti[$riga->id] !== null)
            @if(abs((float)$riga->prezzo_unitario - (float)$prezziSuggeriti[$riga->id]) < 0.005)
              <span class="badge badge-info ml-1" title="Prezzo calcolato dalla matrice">matrice</span>
            @else
              <span class="badge badge-warning ml-1" title="Prezzo modificato manualmente">manuale</span>
            @endif
          @endif
          @if($riga->in_garanzia)
          <span class="badge badge-warning ml-1"><i class="fas fa-shield-alt mr-1"></i>Garanzia</span>
          @if($riga->casaMadre)
          <small class="text-muted ml-1">{{ $riga->casaMadre->ragione_sociale }}</small>
          @endif
          @endif
        </div>
        @if($riga->tipo->value !== 'nota')
        <div class="col-md-1 text-right">{{ $riga->quantita }} ×</div>
        <div class="col-md-1 text-right">€ {{ number_format($riga->prezzo_unitario, 2, ',', '.') }}</div>
        <div class="col-md-1 text-right text-muted">
          @if($riga->sconto_percentuale > 0) -{{ $riga->sconto_percentuale }}% @endif
        </div>
        <div class="col-md-1 text-right font-weight-bold">€ {{ number_format($riga->imponibile, 2, ',', '.') }}</div>
        @else
        <div class="col-md-4"></div>
        @endif
        @can('update', $commessa)
        <div class="col-auto ml-auto">
          <button wire:click="apriModal({{ $riga->id }})" class="btn btn-xs btn-info">
            <i class="fas fa-edit"></i>
          </button>
          <button wire:click="elimina({{ $riga->id }})"
            wire:confirm="Eliminare questa riga?"
            class="btn btn-xs btn-danger">
            <i class="fas fa-trash"></i>
          </button>
        </div>
        @endcan
      </div>
    </div>
    @empty
    <p class="text-muted text-center py-3">Nessuna riga. Aggiungi lavorazioni, articoli o note.</p>
    @endforelse
  </div>

  <!-- Totali -->
  @if($righe->where('tipo.value', '!=', 'nota')->count() > 0)
  @php
    $imponibile     = $righe->sum(fn($r) => $r->imponibile);
    $iva            = $righe->sum(fn($r) => $r->iva);
    $totCliente     = $righe->sum(fn($r) => $r->totale_cliente);
    $totCasaMadre   = $righe->sum(fn($r) => $r->totale_casa_madre);
    $haGaranzia     = $righe->where('in_garanzia', true)->count() > 0;
  @endphp
  <div class="card card-body bg-light mt-3">
    <div class="row">
      <div class="{{ $haGaranzia ? 'col-md-5 offset-md-2' : 'col-md-6 offset-md-6' }}">
        <table class="table table-sm table-borderless mb-0">
          <tr><td>Imponibile totale</td><td class="text-right">€ {{ number_format($imponibile, 2, ',', '.') }}</td></tr>
          <tr><td>IVA</td><td class="text-right">€ {{ number_format($iva, 2, ',', '.') }}</td></tr>
          <tr class="font-weight-bold border-top">
            <td>Totale</td>
            <td class="text-right">€ {{ number_format($imponibile + $iva, 2, ',', '.') }}</td>
          </tr>
        </table>
      </div>
      @if($haGaranzia)
      <div class="col-md-5">
        <table class="table table-sm table-borderless mb-0">
          <tr class="text-success font-weight-bold">
            <td><i class="fas fa-user mr-1"></i>A carico cliente</td>
            <td class="text-right">€ {{ number_format($totCliente, 2, ',', '.') }}</td>
          </tr>
          <tr class="text-warning font-weight-bold">
            <td><i class="fas fa-building mr-1"></i>A carico casa madre</td>
            <td class="text-right">€ {{ number_format($totCasaMadre, 2, ',', '.') }}</td>
          </tr>
        </table>
      </div>
      @endif
    </div>
  </div>
  @endif

  <!-- ====== Modal Aggiungi/Modifica Riga ====== -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica Riga' : 'Nuova Riga' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tipo *</label>
            <select wire:model.live="tipo" class="form-control @error('tipo') is-invalid @enderror">
              @foreach($tipiRiga as $t)
              <option value="{{ $t->value }}">{{ $t->label() }}</option>
              @endforeach
            </select>
            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          @if($tipo === 'manodopera')
          <div class="form-group">
            <label>Tariffa oraria</label>
            <select wire:model.live="tariffa_oraria_id" class="form-control form-control-sm">
              <option value="">— Nessuna (inserimento manuale) —</option>
              @foreach($tariffeOrarie as $to)
              <option value="{{ $to->id }}">{{ $to->nome }} — € {{ number_format($to->tariffa_oraria, 2, ',', '.') }}/h</option>
              @endforeach
            </select>
            <small class="text-muted">Seleziona per precompilare la tariffa oraria.</small>
          </div>
          <div class="form-group position-relative">
            <label>Cerca dal tariffario (operazioni)</label>
            <input wire:model.live.debounce.300ms="cercaTariffa" type="text"
              class="form-control form-control-sm"
              placeholder="Cerca per codice o descrizione…"
              autocomplete="off">
            @if(count($suggerimentiTariffe) > 0)
            <div class="list-group position-absolute w-100" style="z-index:1050;top:100%">
              @foreach($suggerimentiTariffe as $t)
              <button type="button" wire:click="selezionaTariffa({{ $t['id'] }})"
                class="list-group-item list-group-item-action py-1 px-2">
                <span class="font-weight-bold">{{ $t['codice'] }}</span>
                — {{ $t['descrizione'] }}
                <span class="float-right text-muted small">
                  {{ $t['minuti_standard'] }}min — € {{ number_format($t['prezzo_listino'], 2, ',', '.') }}
                </span>
              </button>
              @endforeach
            </div>
            @endif
            @if($tariffa_manodopera_id)
            <small class="text-success"><i class="fas fa-check-circle"></i> Tariffa collegata.</small>
            @endif
          </div>
          @endif

          @if($tipo === 'articolo')
          <div class="form-group position-relative">
            <label>Cerca articolo da catalogo</label>
            <input wire:model.live.debounce.300ms="cercaArticolo" type="text"
              class="form-control form-control-sm"
              placeholder="Cerca per codice o descrizione…"
              autocomplete="off">
            @if(count($suggerimentiArticolo) > 0)
            <div class="list-group position-absolute w-100" style="z-index:1050;top:100%">
              @foreach($suggerimentiArticolo as $sug)
              <button type="button" wire:click="selezionaArticolo({{ $sug['id'] }})"
                class="list-group-item list-group-item-action py-1 px-2">
                <span class="font-weight-bold">{{ $sug['codice'] }}</span>
                — {{ $sug['descrizione'] }}
                <span class="float-right text-muted small">
                  Giacenza: <strong class="{{ $sug['giacenza_attuale'] <= 0 ? 'text-danger' : '' }}">{{ $sug['giacenza_attuale'] }}</strong>
                  | € {{ number_format($sug['prezzo_vendita'], 2, ',', '.') }}
                </span>
              </button>
              @endforeach
            </div>
            @endif
            @if($articolo_id)
            <small class="text-success">
              <i class="fas fa-check-circle"></i> Articolo collegato — Giacenza disponibile:
              <strong class="{{ $giacenzaInsuffciente ? 'text-danger' : 'text-success' }}">{{ $giacenzaDisponibile }}</strong>
              @if($giacenzaInsuffciente)
              <span class="text-warning ml-1"><i class="fas fa-exclamation-triangle"></i> Giacenza insufficiente, lo scarico avverrà al completamento con segnalazione.</span>
              @endif
            </small>
            @endif
          </div>
          @endif

          <div class="form-group">
            <label>Descrizione *</label>
            <input wire:model="descrizione" type="text" class="form-control @error('descrizione') is-invalid @enderror">
            @error('descrizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          @if($tipo !== 'nota')
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Quantità *</label>
                <input wire:model.live="quantita" type="number" step="0.01" class="form-control @error('quantita') is-invalid @enderror">
                @error('quantita')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            @if($tipo === 'manodopera')
            <div class="col-md-4">
              <div class="form-group">
                <label>Ore prev.</label>
                <input wire:model="ore_preventivate" type="number" step="0.01" min="0" class="form-control" placeholder="Stima ore">
                <small class="text-muted">Ore preventivate (budget)</small>
              </div>
            </div>
            @endif
            <div class="col-md-4">
              <div class="form-group">
                <label>Prezzo Unitario € *</label>
                <input wire:model="prezzo_unitario" type="number" step="0.01" class="form-control @error('prezzo_unitario') is-invalid @enderror">
                @error('prezzo_unitario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($tipo === 'articolo' && $articolo_id)
                <button type="button" wire:click="riapplicaMatrice" class="btn btn-xs btn-outline-info mt-1">
                  <i class="fas fa-sync-alt"></i> Riapplica matrice
                </button>
                @endif
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Sconto %</label>
                <input wire:model="sconto_percentuale" type="number" step="0.01" class="form-control">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>IVA %</label>
            <input wire:model="iva_percentuale" type="number" step="0.01" class="form-control">
          </div>

          <!-- Garanzia -->
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="in_garanzia_toggle"
                wire:model.live="inGaranzia">
              <label class="custom-control-label font-weight-bold" for="in_garanzia_toggle">
                <i class="fas fa-shield-alt mr-1 text-warning"></i>Intervento in garanzia
              </label>
            </div>
            <small class="text-muted">Se attivato, il prezzo non viene addebitato al cliente ma fatturato alla casa madre.</small>
          </div>

          @if($inGaranzia)
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Garanzia collegata</label>
                <select wire:model="garanziaId" class="form-control form-control-sm">
                  <option value="">— Seleziona garanzia —</option>
                  @foreach($garanzieAttive as $g)
                  <option value="{{ $g->id }}">{{ $g->tipo->label() }} — {{ $g->descrizione }}
                    @if($g->numero_pratica) [{{ $g->numero_pratica }}]@endif</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Casa Madre da fatturare</label>
                <select wire:model="casaMadreId" class="form-control form-control-sm">
                  <option value="">— Nessuna (garanzia interna) —</option>
                  @foreach($caseMadri as $cm)
                  <option value="{{ $cm->id }}">{{ $cm->ragione_sociale }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="alert alert-warning py-1 px-2 mb-0">
            <small><i class="fas fa-info-circle mr-1"></i>
              Il prezzo unitario rimarrà visibile per la fatturazione alla casa madre.
              Il cliente vedrà questa riga come <strong>gratuita</strong>.
            </small>
          </div>
          @endif
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salva" wire:loading.attr="disabled">
            <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- ====== Modal Applica Pacchetto ====== -->
  @if($showPacchettoModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-box-open mr-2"></i>
            Applica Pacchetto
            <small class="text-muted ml-2">Passo {{ $passoPacchetto }} di 2</small>
          </h5>
          <button type="button" class="close" wire:click="$set('showPacchettoModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">

          @if($passoPacchetto === 1)
          <!-- Step 1: Ricerca pacchetto -->
          <div class="form-group position-relative">
            <label>Cerca pacchetto <small class="text-muted">(filtrato automaticamente per compatibilità con questa commessa)</small></label>
            <input wire:model.live.debounce.300ms="cercaPacchetto" type="text"
              class="form-control"
              placeholder="Nome o descrizione pacchetto…"
              autocomplete="off">
            @if(count($suggerimentiPacchetti) > 0)
            <div class="list-group position-absolute w-100 shadow" style="z-index:1050;top:100%">
              @foreach($suggerimentiPacchetti as $p)
              <button type="button" wire:click="selezionaPacchettoPreview({{ $p['id'] }})"
                class="list-group-item list-group-item-action py-2 px-3">
                <div class="d-flex justify-content-between">
                  <strong>{{ $p['nome'] }}</strong>
                  <span class="text-muted small">Usato {{ $p['utilizzi'] }}×</span>
                </div>
                @if($p['descrizione'])
                <small class="text-muted">{{ Str::limit($p['descrizione'], 80) }}</small><br>
                @endif
                <small>
                  <span class="badge badge-secondary">{{ $p['righe_count'] }} righe</span>
                  <span class="badge badge-success ml-1">Totale: € {{ number_format($p['totale'], 2, ',', '.') }}</span>
                </small>
              </button>
              @endforeach
            </div>
            @elseif(strlen($cercaPacchetto) >= 2 && count($suggerimentiPacchetti) === 0)
            <div class="alert alert-warning mt-2 mb-0">
              Nessun pacchetto compatibile trovato. Verifica i filtri di compatibilità del pacchetto (tipo commessa, veicolo, alimentazione).
            </div>
            @endif
          </div>

          <p class="text-muted text-center mt-4">Digita il nome del pacchetto per cercarlo.</p>

          @else
          <!-- Step 2: Anteprima e personalizzazione -->
          <div class="d-flex justify-content-between mb-3">
            <button wire:click="$set('passoPacchetto', 1)" class="btn btn-sm btn-link text-muted p-0">
              <i class="fas fa-arrow-left"></i> Cambia pacchetto
            </button>
          </div>

          <p class="text-muted small mb-2">Modifica quantità e prezzi prima di confermare. Le righe saranno aggiunte alla commessa.</p>

          <table class="table table-sm table-bordered">
            <thead class="thead-light">
              <tr>
                <th style="width:90px">Tipo</th>
                <th>Descrizione</th>
                <th style="width:80px">Qtà</th>
                <th style="width:100px">Prezzo €</th>
                <th style="width:80px">Sconto%</th>
                <th style="width:70px">IVA%</th>
                <th style="width:90px">Totale €</th>
              </tr>
            </thead>
            <tbody>
              @foreach($righePreview as $i => $riga)
              <tr wire:key="prev-{{ $i }}">
                <td>
                  <span class="badge badge-{{ $riga['tipo'] === 'manodopera' ? 'primary' : ($riga['tipo'] === 'nota' ? 'light text-dark' : 'secondary') }}">
                    {{ ucfirst($riga['tipo']) }}
                  </span>
                </td>
                <td>
                  <input wire:model.lazy="righePreview.{{ $i }}.descrizione" type="text" class="form-control form-control-sm">
                </td>
                @if($riga['tipo'] !== 'nota')
                <td><input wire:model.lazy="righePreview.{{ $i }}.quantita" type="number" step="0.01" min="0" class="form-control form-control-sm text-right"></td>
                <td><input wire:model.lazy="righePreview.{{ $i }}.prezzo_unitario" type="number" step="0.01" min="0" class="form-control form-control-sm text-right"></td>
                <td><input wire:model.lazy="righePreview.{{ $i }}.sconto_percentuale" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-right"></td>
                <td><input wire:model.lazy="righePreview.{{ $i }}.iva_percentuale" type="number" step="0.01" class="form-control form-control-sm text-right"></td>
                <td class="text-right font-weight-bold">
                  @php
                    $imp = (float)$riga['quantita'] * (float)$riga['prezzo_unitario'] * (1 - (float)$riga['sconto_percentuale'] / 100);
                    $tot = $imp * (1 + (float)$riga['iva_percentuale'] / 100);
                  @endphp
                  € {{ number_format($tot, 2, ',', '.') }}
                </td>
                @else
                <td colspan="4" class="text-muted small text-center">nota</td>
                <td>—</td>
                @endif
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="font-weight-bold table-active">
                <td colspan="6" class="text-right">Totale (IVA inclusa):</td>
                <td class="text-right">€ {{ number_format($totalePreview, 2, ',', '.') }}</td>
              </tr>
            </tfoot>
          </table>
          @endif

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showPacchettoModal', false)">Annulla</button>
          @if($passoPacchetto === 2)
          <button type="button" class="btn btn-success" wire:click="confermaPacchetto" wire:loading.attr="disabled">
            <span wire:loading wire:target="confermaPacchetto" class="spinner-border spinner-border-sm mr-1"></span>
            <i class="fas fa-check"></i> Aggiungi alla commessa
          </button>
          @endif
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
