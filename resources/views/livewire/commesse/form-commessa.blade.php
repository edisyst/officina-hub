<div>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Nuova Commessa</h3>
        </div>
        <div class="card-body">

          <!-- Ricerca Cliente -->
          <div class="form-group">
            <label>Cliente *</label>
            <div class="position-relative">
              <input wire:model.live.debounce.300ms="searchCliente" type="text"
                class="form-control @error('clienteSelezionatoId') is-invalid @enderror"
                placeholder="Cerca per nome, cognome, CF..."
                autocomplete="off">
              @if($suggerimentiClienti->isNotEmpty())
              <div class="list-group position-absolute w-100 shadow" style="z-index:1000;top:38px">
                @foreach($suggerimentiClienti as $c)
                <button type="button" class="list-group-item list-group-item-action"
                  wire:click="selezionaCliente({{ $c->id }})">
                  <strong>{{ $c->nome_completo }}</strong>
                  @if($c->telefono) <small class="text-muted ml-2">📞 {{ $c->telefono }}</small> @endif
                </button>
                @endforeach
              </div>
              @endif
            </div>
            @if($clienteSelezionato)
            <div class="alert alert-success py-1 mt-1">
              <i class="fas fa-check-circle"></i>
              Cliente: <strong>{{ $clienteSelezionato->nome_completo }}</strong>
              <button type="button" class="btn btn-xs btn-link text-danger"
                wire:click="$set('clienteSelezionatoId', null); $set('clienteSelezionato', null); $set('searchCliente', '')">
                ✕ Cambia
              </button>
            </div>
            @endif
            @error('clienteSelezionatoId')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <!-- Ricerca Veicolo -->
          <div class="form-group">
            <label>Veicolo *</label>
            <div class="d-flex gap-2 mb-2">
              <button type="button" class="btn btn-sm {{ ! $showNuovoVeicolo ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="$set('showNuovoVeicolo', false)">
                Cerca veicolo esistente
              </button>
              <button type="button" class="btn btn-sm {{ $showNuovoVeicolo ? 'btn-success' : 'btn-outline-success' }}"
                wire:click="$set('showNuovoVeicolo', true); $set('veicoloSelezionatoId', null)">
                <i class="fas fa-plus"></i> Nuovo veicolo al volo
              </button>
            </div>

            @if(! $showNuovoVeicolo)
            <div class="position-relative">
              <input wire:model.live.debounce.300ms="searchVeicolo" type="text"
                class="form-control @error('veicoloSelezionatoId') is-invalid @enderror"
                placeholder="Cerca per targa, VIN, marca, modello..."
                autocomplete="off">
              @if($suggerimentiVeicoli->isNotEmpty())
              <div class="list-group position-absolute w-100 shadow" style="z-index:1000;top:38px">
                @foreach($suggerimentiVeicoli as $v)
                <button type="button" class="list-group-item list-group-item-action"
                  wire:click="selezionaVeicolo({{ $v->id }})">
                  <strong>{{ $v->targa ?? 'Senza targa' }}</strong>
                  — {{ $v->marca }} {{ $v->modello }} {{ $v->versione }}
                  <small class="text-muted ml-2">{{ $v->alimentazione->label() }}</small>
                </button>
                @endforeach
              </div>
              @endif
            </div>
            @if($veicoloSelezionato)
            <div class="alert alert-success py-1 mt-1">
              <i class="fas fa-check-circle"></i>
              Veicolo: <strong>{{ $veicoloSelezionato->targa ?? '-' }} — {{ $veicoloSelezionato->marca }} {{ $veicoloSelezionato->modello }}</strong>
              <button type="button" class="btn btn-xs btn-link text-danger"
                wire:click="$set('veicoloSelezionatoId', null); $set('veicoloSelezionato', null); $set('searchVeicolo', '')">
                ✕ Cambia
              </button>
            </div>
            @endif
            @endif

            @if($showNuovoVeicolo)
            <div class="card card-body bg-light mt-2">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Tipo *</label>
                    <select wire:model="nv_tipo" class="form-control form-control-sm">
                      <option value="auto">Auto</option>
                      <option value="moto">Moto</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Targa</label>
                    <input wire:model="nv_targa" type="text" class="form-control form-control-sm" style="text-transform:uppercase">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Alimentazione</label>
                    <select wire:model="nv_alimentazione" class="form-control form-control-sm">
                      <option value="benzina">Benzina</option>
                      <option value="diesel">Diesel</option>
                      <option value="ibrido">Ibrido</option>
                      <option value="elettrico">Elettrico</option>
                      <option value="gpl">GPL</option>
                      <option value="metano">Metano</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Marca *</label>
                    <input wire:model="nv_marca" type="text" class="form-control form-control-sm @error('nv_marca') is-invalid @enderror">
                    @error('nv_marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Modello *</label>
                    <input wire:model="nv_modello" type="text" class="form-control form-control-sm @error('nv_modello') is-invalid @enderror">
                    @error('nv_modello')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
            </div>
            @endif

            @error('veicoloSelezionatoId')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <!-- Tipo e KM -->
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Tipo Commessa *</label>
                <select wire:model="tipo" class="form-control @error('tipo') is-invalid @enderror">
                  @foreach($tipi as $t)
                  <option value="{{ $t->value }}">{{ $t->label() }}</option>
                  @endforeach
                </select>
                @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>KM Ingresso</label>
                <input wire:model="km_ingresso" type="number" class="form-control" min="0">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Data Uscita Prevista</label>
                <input wire:model="data_uscita_prevista" type="date" class="form-control">
              </div>
            </div>
          </div>

          <!-- Descrizione -->
          <div class="form-group">
            <label>Descrizione del Problema (parole del cliente) *</label>
            <textarea wire:model="descrizione_cliente" class="form-control @error('descrizione_cliente') is-invalid @enderror"
              rows="4" placeholder="Descrivi il problema con le parole del cliente..."></textarea>
            @error('descrizione_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <!-- Preventivo Rapido -->
          <div class="card card-outline card-success mb-0">
            <div class="card-header py-2" style="cursor:pointer" wire:click="togglePreventivoRapido">
              <h3 class="card-title mb-0">
                <i class="fas fa-bolt mr-1 text-success"></i>
                Preventivo Rapido (opzionale)
                <small class="text-muted ml-2">— applica un pacchetto direttamente all'accettazione</small>
              </h3>
              <div class="card-tools">
                <i class="fas fa-{{ $showPreventivoRapido ? 'minus' : 'plus' }}-circle text-success"></i>
              </div>
            </div>
            @if($showPreventivoRapido)
            <div class="card-body">
              <!-- Ricerca pacchetto -->
              <div class="form-group position-relative">
                <label>Cerca pacchetto di servizio</label>
                <input wire:model.live.debounce.300ms="cercaPacchettoRapido" type="text"
                  class="form-control" placeholder="es. Tagliando benzina…" autocomplete="off">
                @if(count($suggerimentiPacchettiRapidi) > 0)
                <div class="list-group position-absolute w-100 shadow" style="z-index:1000;top:100%">
                  @foreach($suggerimentiPacchettiRapidi as $p)
                  <button type="button" wire:click="selezionaPacchettoRapido({{ $p['id'] }})"
                    class="list-group-item list-group-item-action py-2 px-3">
                    <div class="d-flex justify-content-between">
                      <strong>{{ $p['nome'] }}</strong>
                      <span class="text-muted small">{{ $p['righe_count'] }} righe — € {{ number_format($p['totale'], 2, ',', '.') }}</span>
                    </div>
                  </button>
                  @endforeach
                </div>
                @endif
              </div>

              @if(count($righePreventivo) > 0)
              <table class="table table-sm table-bordered mt-2">
                <thead class="thead-light">
                  <tr>
                    <th style="width:90px">Tipo</th>
                    <th>Descrizione</th>
                    <th style="width:80px">Qtà</th>
                    <th style="width:100px">Prezzo €</th>
                    <th style="width:90px">Totale €</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($righePreventivo as $i => $riga)
                  <tr>
                    <td><span class="badge badge-{{ $riga['tipo'] === 'manodopera' ? 'primary' : ($riga['tipo'] === 'nota' ? 'light text-dark' : 'secondary') }}">{{ ucfirst($riga['tipo']) }}</span></td>
                    <td><input wire:model.lazy="righePreventivo.{{ $i }}.descrizione" type="text" class="form-control form-control-sm"></td>
                    @if($riga['tipo'] !== 'nota')
                    <td><input wire:model.lazy="righePreventivo.{{ $i }}.quantita" type="number" step="0.01" min="0" class="form-control form-control-sm text-right"></td>
                    <td><input wire:model.lazy="righePreventivo.{{ $i }}.prezzo_unitario" type="number" step="0.01" min="0" class="form-control form-control-sm text-right"></td>
                    <td class="text-right font-weight-bold">
                      @php
                        $imp = (float)$riga['quantita'] * (float)$riga['prezzo_unitario'] * (1 - (float)$riga['sconto_percentuale'] / 100);
                        $tot = $imp * (1 + (float)$riga['iva_percentuale'] / 100);
                      @endphp
                      € {{ number_format($tot, 2, ',', '.') }}
                    </td>
                    @else
                    <td colspan="3" class="text-muted small text-center">nota</td>
                    <td>—</td>
                    @endif
                  </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr class="font-weight-bold table-active">
                    <td colspan="4" class="text-right">Totale preventivo (IVA inclusa):</td>
                    <td class="text-right">€ {{ number_format($totalePreventivoRapido, 2, ',', '.') }}</td>
                  </tr>
                </tfoot>
              </table>
              <p class="text-muted small mb-0">
                <i class="fas fa-info-circle"></i>
                Queste righe saranno aggiunte automaticamente alla commessa al salvataggio.
              </p>
              @endif
            </div>
            @endif
          </div>
        </div>
        <div class="card-footer">
          <a href="{{ route('commesse.index') }}" class="btn btn-secondary">Annulla</a>
          <button type="button" class="btn btn-primary float-right" wire:click="salva" wire:loading.attr="disabled">
            <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
            <i class="fas fa-save"></i> Crea Commessa
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
