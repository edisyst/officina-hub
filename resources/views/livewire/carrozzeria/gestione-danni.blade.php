<div>
  <div class="row">
    {{-- SVG Interattivo Veicolo --}}
    <div class="col-md-4">
      <div class="card card-outline card-secondary">
        <div class="card-header py-2">
          <h3 class="card-title text-sm">Mappa Danni Veicolo</h3>
        </div>
        <div class="card-body p-2 d-flex justify-content-center"
          x-data="{
            danniPerZona: @js($danniPerZona),
            colore(zona) {
              const c = this.danniPerZona[zona] || 0;
              return c === 0 ? '#f1f3f4' : (c === 1 ? '#fff3cd' : '#f8d7da');
            },
            stroke(zona) {
              const c = this.danniPerZona[zona] || 0;
              return c === 0 ? '#dee2e6' : (c === 1 ? '#ffc107' : '#dc3545');
            }
          }">
          <svg viewBox="0 0 300 450" xmlns="http://www.w3.org/2000/svg"
            style="max-width:260px;width:100%">

            <text x="150" y="11" text-anchor="middle" font-size="9" fill="#6c757d">↑ ANTERIORE</text>

            {{-- Anteriore SX --}}
            <rect x="10" y="15" width="90" height="75" rx="4"
              :fill="colore('anteriore_sx')" :stroke="stroke('anteriore_sx')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('anteriore_sx')"/>
            <text x="55" y="56" text-anchor="middle" font-size="8" fill="#333" pointer-events="none">Ant. SX</text>
            <text x="55" y="67" text-anchor="middle" font-size="9" fill="#555" pointer-events="none"
              x-show="danniPerZona['anteriore_sx']" x-text="'(' + (danniPerZona['anteriore_sx']||0) + ')'"></text>

            {{-- Anteriore Centro --}}
            <rect x="105" y="15" width="90" height="75" rx="4"
              :fill="colore('anteriore_centro')" :stroke="stroke('anteriore_centro')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('anteriore_centro')"/>
            <text x="150" y="56" text-anchor="middle" font-size="8" fill="#333" pointer-events="none">Ant. Centro</text>

            {{-- Anteriore DX --}}
            <rect x="200" y="15" width="90" height="75" rx="4"
              :fill="colore('anteriore_dx')" :stroke="stroke('anteriore_dx')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('anteriore_dx')"/>
            <text x="245" y="56" text-anchor="middle" font-size="8" fill="#333" pointer-events="none">Ant. DX</text>

            {{-- Laterale SX Anteriore --}}
            <rect x="10" y="95" width="60" height="120" rx="4"
              :fill="colore('laterale_sx_ant')" :stroke="stroke('laterale_sx_ant')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('laterale_sx_ant')"/>
            <text x="40" y="158" text-anchor="middle" font-size="7" fill="#333"
              transform="rotate(-90 40 155)" pointer-events="none">Lat. SX Ant.</text>

            {{-- Tetto (zona centrale) --}}
            <rect x="70" y="95" width="160" height="240" rx="4"
              :fill="colore('tetto')" :stroke="stroke('tetto')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('tetto')"/>
            <text x="150" y="218" text-anchor="middle" font-size="9" fill="#333" pointer-events="none">TETTO</text>
            {{-- Parabrezza decorativo --}}
            <rect x="85" y="110" width="130" height="55" rx="3" fill="rgba(200,220,255,0.4)" pointer-events="none"/>
            {{-- Lunotto decorativo --}}
            <rect x="85" y="275" width="130" height="50" rx="3" fill="rgba(200,220,255,0.4)" pointer-events="none"/>

            {{-- Laterale DX Anteriore --}}
            <rect x="230" y="95" width="60" height="120" rx="4"
              :fill="colore('laterale_dx_ant')" :stroke="stroke('laterale_dx_ant')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('laterale_dx_ant')"/>
            <text x="260" y="158" text-anchor="middle" font-size="7" fill="#333"
              transform="rotate(90 260 155)" pointer-events="none">Lat. DX Ant.</text>

            {{-- Laterale SX Posteriore --}}
            <rect x="10" y="220" width="60" height="115" rx="4"
              :fill="colore('laterale_sx_post')" :stroke="stroke('laterale_sx_post')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('laterale_sx_post')"/>
            <text x="40" y="280" text-anchor="middle" font-size="7" fill="#333"
              transform="rotate(-90 40 278)" pointer-events="none">Lat. SX Post.</text>

            {{-- Laterale DX Posteriore --}}
            <rect x="230" y="220" width="60" height="115" rx="4"
              :fill="colore('laterale_dx_post')" :stroke="stroke('laterale_dx_post')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('laterale_dx_post')"/>
            <text x="260" y="280" text-anchor="middle" font-size="7" fill="#333"
              transform="rotate(90 260 278)" pointer-events="none">Lat. DX Post.</text>

            {{-- Posteriore SX --}}
            <rect x="10" y="340" width="90" height="75" rx="4"
              :fill="colore('posteriore_sx')" :stroke="stroke('posteriore_sx')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('posteriore_sx')"/>
            <text x="55" y="382" text-anchor="middle" font-size="8" fill="#333" pointer-events="none">Post. SX</text>

            {{-- Posteriore Centro --}}
            <rect x="105" y="340" width="90" height="75" rx="4"
              :fill="colore('posteriore_centro')" :stroke="stroke('posteriore_centro')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('posteriore_centro')"/>
            <text x="150" y="382" text-anchor="middle" font-size="8" fill="#333" pointer-events="none">Post. Centro</text>

            {{-- Posteriore DX --}}
            <rect x="200" y="340" width="90" height="75" rx="4"
              :fill="colore('posteriore_dx')" :stroke="stroke('posteriore_dx')"
              stroke-width="1.5" style="cursor:pointer"
              wire:click="apriModalDanno('posteriore_dx')"/>
            <text x="245" y="382" text-anchor="middle" font-size="8" fill="#333" pointer-events="none">Post. DX</text>

            <text x="150" y="445" text-anchor="middle" font-size="9" fill="#6c757d">↓ POSTERIORE</text>

            {{-- Sottoscocca (extra) --}}
          </svg>
        </div>
        <div class="card-footer py-2 text-center">
          <button wire:click="apriModalDanno('sottoscocca')" class="btn btn-xs btn-outline-secondary mr-1">
            + Sottoscocca
          </button>
          <button wire:click="apriModalDanno('altro')" class="btn btn-xs btn-outline-secondary">
            + Altro
          </button>
        </div>
      </div>
      {{-- Legenda --}}
      <div class="d-flex align-items-center mt-2 small text-muted">
        <span class="d-inline-block mr-1" style="width:14px;height:14px;background:#f1f3f4;border:1px solid #dee2e6;border-radius:2px"></span> Nessun danno
        <span class="d-inline-block mx-1 ml-3" style="width:14px;height:14px;background:#fff3cd;border:1px solid #ffc107;border-radius:2px"></span> 1 danno
        <span class="d-inline-block mx-1 ml-3" style="width:14px;height:14px;background:#f8d7da;border:1px solid #dc3545;border-radius:2px"></span> Danni multipli
      </div>
    </div>

    {{-- Tabella Danni --}}
    <div class="col-md-8">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Elenco Danni ({{ $danni->count() }})</h6>
        <button wire:click="apriModalDanno('')" class="btn btn-sm btn-primary">
          <i class="fas fa-plus mr-1"></i> Aggiungi Danno
        </button>
      </div>

      <table class="table table-sm table-hover">
        <thead class="thead-light">
          <tr>
            <th>Zona</th>
            <th>Tipo</th>
            <th>Descrizione</th>
            <th class="text-right">Q.tà</th>
            <th class="text-right">Stima €</th>
            <th class="text-right">Perizia €</th>
            <th class="text-center">In perizia</th>
            <th width="70"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($danni as $danno)
          <tr>
            <td><small>{{ $danno->zona->label() }}</small></td>
            <td><small>{{ $danno->tipo_danno->label() }}</small></td>
            <td>{{ $danno->descrizione }}</td>
            <td class="text-right">{{ number_format($danno->quantita, 2, ',', '.') }}</td>
            <td class="text-right">{{ $danno->prezzo_stimato !== null ? number_format($danno->prezzo_stimato, 2, ',', '.') : '—' }}</td>
            <td class="text-right">{{ $danno->prezzo_perizia !== null ? number_format($danno->prezzo_perizia, 2, ',', '.') : '—' }}</td>
            <td class="text-center">
              @if($danno->incluso_in_perizia)
                <i class="fas fa-check text-success"></i>
              @else
                <i class="fas fa-times text-danger"></i>
              @endif
            </td>
            <td>
              <button wire:click="apriModifica({{ $danno->id }})" class="btn btn-xs btn-outline-primary">
                <i class="fas fa-edit"></i>
              </button>
              <button wire:click="eliminaDanno({{ $danno->id }})"
                wire:confirm="Eliminare questo danno?"
                class="btn btn-xs btn-outline-danger">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr><td colspan="8" class="text-center text-muted py-3">Nessun danno registrato. Clicca su una zona del veicolo per aggiungere un danno.</td></tr>
          @endforelse
        </tbody>
        @if($danni->count() > 0)
        <tfoot>
          <tr class="font-weight-bold">
            <td colspan="4" class="text-right">Totali:</td>
            <td class="text-right">€ {{ number_format($totaleStimato, 2, ',', '.') }}</td>
            <td class="text-right text-success">€ {{ number_format($totalePerizia, 2, ',', '.') }}</td>
            <td colspan="2"></td>
          </tr>
          <tr class="text-muted small">
            <td colspan="4" class="text-right">Differenza (non coperta):</td>
            <td class="text-right text-danger" colspan="4">
              € {{ number_format($totaleStimato - $totalePerizia, 2, ',', '.') }}
            </td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>

  {{-- Modal Aggiungi/Modifica Danno --}}
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $editingId ? 'Modifica Danno' : 'Aggiungi Danno' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Zona <span class="text-danger">*</span></label>
                <select wire:model="formZona" class="form-control form-control-sm @error('formZona') is-invalid @enderror">
                  <option value="">— Seleziona zona —</option>
                  @foreach($zone as $zona)
                  <option value="{{ $zona->value }}">{{ $zona->label() }}</option>
                  @endforeach
                </select>
                @error('formZona')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Tipo Danno <span class="text-danger">*</span></label>
                <select wire:model="formTipoDanno" class="form-control form-control-sm @error('formTipoDanno') is-invalid @enderror">
                  <option value="">— Seleziona tipo —</option>
                  @foreach($tipiDanno as $tipo)
                  <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                  @endforeach
                </select>
                @error('formTipoDanno')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Descrizione <span class="text-danger">*</span></label>
                <input type="text" wire:model="formDescrizione"
                  class="form-control form-control-sm @error('formDescrizione') is-invalid @enderror"
                  placeholder="Descrizione del danno">
                @error('formDescrizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Quantità</label>
                <input type="number" step="0.01" min="0.01" wire:model="formQuantita"
                  class="form-control form-control-sm @error('formQuantita') is-invalid @enderror">
                @error('formQuantita')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Prezzo Stimato (€)</label>
                <input type="number" step="0.01" min="0" wire:model="formPrezzoStimato"
                  class="form-control form-control-sm @error('formPrezzoStimato') is-invalid @enderror"
                  placeholder="0.00">
                @error('formPrezzoStimato')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Prezzo Perizia (€)</label>
                <input type="number" step="0.01" min="0" wire:model="formPrezzoPerzia"
                  class="form-control form-control-sm @error('formPrezzoPerzia') is-invalid @enderror"
                  placeholder="0.00">
                @error('formPrezzoPerzia')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group pt-2 mt-4">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="formIncluso"
                    wire:model="formIncluso">
                  <label class="custom-control-label" for="formIncluso">Incluso in perizia</label>
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Note</label>
                <textarea wire:model="formNote" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Annulla</button>
          <button type="button" class="btn btn-primary" wire:click="salvaDanno" wire:loading.attr="disabled">
            <span wire:loading wire:target="salvaDanno" class="spinner-border spinner-border-sm mr-1"></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
