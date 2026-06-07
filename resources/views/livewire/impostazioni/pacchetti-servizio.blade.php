<div>
  @if (session('success'))
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
  </div>
  @endif

  <!-- Lista pacchetti -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-box-open mr-2"></i>Pacchetti Servizio</h3>
      <div class="card-tools">
        <button wire:click="apriNuovo" class="btn btn-sm btn-primary">
          <i class="fas fa-plus"></i> Nuovo Pacchetto
        </button>
      </div>
    </div>
    <div class="card-body border-bottom pb-3">
      <div class="row">
        <div class="col-md-4">
          <input wire:model.live.debounce.300ms="cerca" type="text"
            class="form-control form-control-sm" placeholder="Cerca pacchetto…">
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroTipoCommessa" class="form-control form-control-sm">
            <option value="">Tipo commessa</option>
            <option value="meccanica">Meccanica</option>
            <option value="carrozzeria">Carrozzeria</option>
            <option value="tagliando">Tagliando</option>
            <option value="entrambi">Entrambi</option>
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroTipoVeicolo" class="form-control form-control-sm">
            <option value="">Tipo veicolo</option>
            <option value="auto">Auto</option>
            <option value="moto">Moto</option>
            <option value="entrambi">Entrambi</option>
          </select>
        </div>
        <div class="col-md-2">
          <select wire:model.live="filtroAlimentazione" class="form-control form-control-sm">
            <option value="">Alimentazione</option>
            <option value="benzina">Benzina</option>
            <option value="diesel">Diesel</option>
            <option value="ibrido">Ibrido</option>
            <option value="elettrico">Elettrico</option>
            <option value="gpl">GPL</option>
            <option value="metano">Metano</option>
            <option value="tutte">Tutte</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Nome</th>
            <th>Compatibilità</th>
            <th class="text-center">Righe</th>
            <th class="text-right">Tot. suggerito</th>
            <th class="text-center">Usato</th>
            <th class="text-center">Stato</th>
            <th class="text-center">Azioni</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pacchetti as $p)
          <tr wire:key="pacchetto-{{ $p->id }}">
            <td>
              <strong>{{ $p->nome }}</strong>
              @if($p->descrizione)
              <br><small class="text-muted">{{ Str::limit($p->descrizione, 60) }}</small>
              @endif
            </td>
            <td>
              <span class="badge badge-light">{{ $p->tipo_commessa === 'entrambi' ? 'Tutte' : ucfirst($p->tipo_commessa) }}</span>
              <span class="badge badge-light">{{ $p->tipo_veicolo === 'entrambi' ? 'Auto+Moto' : ucfirst($p->tipo_veicolo) }}</span>
              @if($p->alimentazione !== 'tutte')
              <span class="badge badge-info">{{ ucfirst($p->alimentazione) }}</span>
              @endif
            </td>
            <td class="text-center">{{ $p->righe_count }}</td>
            <td class="text-right">
              @if($p->prezzo_totale_suggerito)
              <strong>€ {{ number_format((float)$p->prezzo_totale_suggerito, 2, ',', '.') }}</strong>
              @else <span class="text-muted">—</span>
              @endif
            </td>
            <td class="text-center">
              <span class="badge badge-secondary">{{ $p->utilizzi }}×</span>
            </td>
            <td class="text-center">
              <span wire:click="toggleAttivo({{ $p->id }})" style="cursor:pointer"
                class="badge {{ $p->attivo ? 'badge-success' : 'badge-secondary' }}">
                {{ $p->attivo ? 'Attivo' : 'Inattivo' }}
              </span>
            </td>
            <td class="text-center">
              <button wire:click="apriModifica({{ $p->id }})" class="btn btn-xs btn-outline-primary" title="Modifica">
                <i class="fas fa-edit"></i>
              </button>
              <button wire:click="clona({{ $p->id }})" class="btn btn-xs btn-outline-info" title="Clona">
                <i class="fas fa-copy"></i>
              </button>
              <button wire:click="elimina({{ $p->id }})"
                wire:confirm="Eliminare il pacchetto {{ $p->nome }}?"
                class="btn btn-xs btn-outline-danger" title="Elimina">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center text-muted py-4">Nessun pacchetto trovato.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $pacchetti->links() }}</div>
  </div>

  <!-- Modal Crea/Modifica Pacchetto -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);z-index:1040">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $pacchettoId ? 'Modifica Pacchetto' : 'Nuovo Pacchetto' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">

          <!-- Sezione intestazione -->
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Nome pacchetto <span class="text-danger">*</span></label>
                <input wire:model="nome" type="text" class="form-control @error('nome') is-invalid @enderror"
                  placeholder="es. Tagliando completo benzina 1.4">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Tipo commessa</label>
                    <select wire:model="tipo_commessa" class="form-control form-control-sm">
                      <option value="entrambi">Entrambi</option>
                      <option value="meccanica">Meccanica</option>
                      <option value="carrozzeria">Carrozzeria</option>
                      <option value="tagliando">Tagliando</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Tipo veicolo</label>
                    <select wire:model="tipo_veicolo" class="form-control form-control-sm">
                      <option value="entrambi">Entrambi</option>
                      <option value="auto">Auto</option>
                      <option value="moto">Moto</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Alimentazione</label>
                    <select wire:model="alimentazione" class="form-control form-control-sm">
                      <option value="tutte">Tutte</option>
                      <option value="benzina">Benzina</option>
                      <option value="diesel">Diesel</option>
                      <option value="ibrido">Ibrido</option>
                      <option value="elettrico">Elettrico</option>
                      <option value="gpl">GPL</option>
                      <option value="metano">Metano</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Descrizione</label>
                <textarea wire:model="descrizione" class="form-control" rows="2"></textarea>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Note interne</label>
                <textarea wire:model="note" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </div>

          <hr>

          <!-- Sezione righe -->
          <h6 class="mb-3"><i class="fas fa-list mr-1"></i>Righe del pacchetto</h6>

          <!-- Righe esistenti -->
          @if(count($righe) > 0)
          <table class="table table-sm table-bordered mb-3">
            <thead class="thead-light">
              <tr>
                <th style="width:100px">Tipo</th>
                <th>Descrizione</th>
                <th style="width:80px">Qtà</th>
                <th style="width:100px">Prezzo €</th>
                <th style="width:80px">Sconto%</th>
                <th style="width:70px">IVA%</th>
                <th style="width:40px"></th>
              </tr>
            </thead>
            <tbody>
              @foreach($righe as $i => $riga)
              <tr wire:key="riga-preview-{{ $i }}">
                <td>
                  <span class="badge badge-{{ $riga['tipo'] === 'manodopera' ? 'primary' : ($riga['tipo'] === 'nota' ? 'light text-dark' : 'secondary') }}">
                    {{ ucfirst($riga['tipo']) }}
                  </span>
                </td>
                <td>
                  <input wire:model.lazy="righe.{{ $i }}.descrizione" type="text" class="form-control form-control-sm">
                </td>
                @if($riga['tipo'] !== 'nota')
                <td><input wire:model.lazy="righe.{{ $i }}.quantita" type="number" step="0.01" min="0" class="form-control form-control-sm text-right"></td>
                <td><input wire:model.lazy="righe.{{ $i }}.prezzo_unitario" type="number" step="0.01" min="0" class="form-control form-control-sm text-right"></td>
                <td><input wire:model.lazy="righe.{{ $i }}.sconto_percentuale" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-right"></td>
                <td><input wire:model.lazy="righe.{{ $i }}.iva_percentuale" type="number" step="0.01" class="form-control form-control-sm text-right"></td>
                @else
                <td colspan="4" class="text-muted text-center small">nota</td>
                @endif
                <td class="text-center">
                  <button wire:click="rimuoviRiga({{ $i }})" class="btn btn-xs btn-outline-danger">
                    <i class="fas fa-times"></i>
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="font-weight-bold">
                <td colspan="6" class="text-right">Totale (IVA inclusa):</td>
                <td class="text-right">€ {{ number_format($totaleRighe, 2, ',', '.') }}</td>
              </tr>
            </tfoot>
          </table>
          @else
          <p class="text-muted text-center py-2">Nessuna riga. Aggiungi manodopera, articoli o note qui sotto.</p>
          @endif

          <!-- Form aggiunta riga -->
          <div class="card card-outline card-secondary">
            <div class="card-header py-2">
              <div class="btn-group btn-group-sm">
                <button type="button" wire:click="$set('nuovoTipo', 'manodopera')"
                  class="btn {{ $nuovoTipo === 'manodopera' ? 'btn-primary' : 'btn-outline-primary' }}">
                  <i class="fas fa-wrench"></i> Manodopera
                </button>
                <button type="button" wire:click="$set('nuovoTipo', 'articolo')"
                  class="btn {{ $nuovoTipo === 'articolo' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                  <i class="fas fa-box"></i> Articolo
                </button>
                <button type="button" wire:click="$set('nuovoTipo', 'nota')"
                  class="btn {{ $nuovoTipo === 'nota' ? 'btn-light' : 'btn-outline-secondary' }}">
                  <i class="fas fa-sticky-note"></i> Nota
                </button>
              </div>
            </div>
            <div class="card-body py-2">
              <div class="row align-items-end">

                @if($nuovoTipo === 'manodopera')
                <!-- Typeahead tariffa -->
                <div class="col-md-5">
                  <div class="form-group mb-0 position-relative">
                    <label class="small">Cerca tariffa da tariffario</label>
                    <input wire:model.live.debounce.300ms="cercaTariffa" type="text"
                      class="form-control form-control-sm" placeholder="Codice o descrizione…" autocomplete="off">
                    @if(count($suggerimentiTariffe) > 0)
                    <div class="list-group position-absolute w-100 shadow" style="z-index:1060;top:100%">
                      @foreach($suggerimentiTariffe as $t)
                      <button type="button" wire:click="selezionaTariffa({{ $t['id'] }})"
                        class="list-group-item list-group-item-action py-1 px-2 small">
                        <strong>{{ $t['codice'] }}</strong> — {{ $t['descrizione'] }}
                        <span class="float-right text-muted">{{ $t['minuti_standard'] }}min — € {{ number_format($t['prezzo_listino'], 2, ',', '.') }}</span>
                      </button>
                      @endforeach
                    </div>
                    @endif
                  </div>
                </div>
                @elseif($nuovoTipo === 'articolo')
                <!-- Typeahead articolo -->
                <div class="col-md-5">
                  <div class="form-group mb-0 position-relative">
                    <label class="small">Cerca articolo da catalogo</label>
                    <input wire:model.live.debounce.300ms="cercaArticolo" type="text"
                      class="form-control form-control-sm" placeholder="Codice o descrizione…" autocomplete="off">
                    @if(count($suggerimentiArticoli) > 0)
                    <div class="list-group position-absolute w-100 shadow" style="z-index:1060;top:100%">
                      @foreach($suggerimentiArticoli as $a)
                      <button type="button" wire:click="selezionaArticolo({{ $a['id'] }})"
                        class="list-group-item list-group-item-action py-1 px-2 small">
                        <strong>{{ $a['codice'] }}</strong> — {{ $a['descrizione'] }}
                        <span class="float-right text-muted">Giac: {{ $a['giacenza_attuale'] }} | € {{ number_format($a['prezzo_vendita'], 2, ',', '.') }}</span>
                      </button>
                      @endforeach
                    </div>
                    @endif
                  </div>
                </div>
                @endif

                <div class="{{ $nuovoTipo === 'nota' ? 'col-md-7' : 'col-md-4' }}">
                  <div class="form-group mb-0">
                    <label class="small">Descrizione</label>
                    <input wire:model="nuovaDescrizione" type="text" class="form-control form-control-sm @error('nuovaDescrizione') is-invalid @enderror">
                    @error('nuovaDescrizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>

                @if($nuovoTipo !== 'nota')
                <div class="col-md-1">
                  <div class="form-group mb-0">
                    <label class="small">Qtà</label>
                    <input wire:model="nuovaQuantita" type="number" step="0.01" min="0" class="form-control form-control-sm">
                  </div>
                </div>
                <div class="col-md-1">
                  <div class="form-group mb-0">
                    <label class="small">€</label>
                    <input wire:model="nuovaPrezzo" type="number" step="0.01" min="0" class="form-control form-control-sm">
                  </div>
                </div>
                @endif

                <div class="col-auto ml-auto">
                  <button wire:click="aggiungiRiga" class="btn btn-sm btn-success">
                    <i class="fas fa-plus"></i> Aggiungi
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salva" wire:loading.attr="disabled">
            <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
            Salva pacchetto
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
