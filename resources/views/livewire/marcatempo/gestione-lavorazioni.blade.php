<div>
  @if($erroreAvvia)
  <div class="alert alert-warning alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-exclamation-triangle mr-2"></i>{{ $erroreAvvia }}
  </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Lavorazioni della commessa</h6>
    <button wire:click="apriNuova" class="btn btn-sm btn-primary">
      <i class="fas fa-plus"></i> Aggiungi Lavorazione
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>Descrizione</th>
          <th>Meccanico</th>
          <th>Ponte</th>
          <th>Min. Prev.</th>
          <th>Start</th>
          <th>Stop</th>
          <th>Min. Eff.</th>
          <th>Delta</th>
          <th>Azioni</th>
        </tr>
      </thead>
      <tbody>
        @forelse($lavorazioni as $lav)
        <tr>
          <td>{{ $lav->descrizione }}</td>
          <td>{{ $lav->meccanico->name ?? '—' }}</td>
          <td>{{ $lav->ponte->nome ?? '—' }}</td>
          <td class="text-right">{{ $lav->minuti_preventivati }}</td>
          <td>{{ $lav->started_at?->format('d/m H:i') ?? '—' }}</td>
          <td>{{ $lav->stopped_at?->format('d/m H:i') ?? '—' }}</td>
          <td class="text-right">
            @if($lav->is_attiva)
            <span class="badge badge-primary"
              x-data="{
                startTs: {{ $lav->started_at->timestamp * 1000 }},
                timer: '…',
                init() {
                  setInterval(() => {
                    const e = Math.floor((Date.now() - this.startTs) / 60000);
                    this.timer = e + ' min';
                  }, 10000);
                  const e = Math.floor((Date.now() - this.startTs) / 60000);
                  this.timer = e + ' min';
                }
              }" x-text="timer">
            </span>
            @else
            {{ $lav->minuti_effettivi ?? '—' }}
            @endif
          </td>
          <td>
            @if($lav->delta_minuti !== null)
            <span class="badge {{ $lav->delta_minuti <= 0 ? 'badge-success' : 'badge-danger' }}">
              {{ $lav->delta_minuti >= 0 ? '+' : '' }}{{ $lav->delta_minuti }}
            </span>
            @else
            —
            @endif
          </td>
          <td>
            @if(! $lav->is_attiva && ! $lav->stopped_at)
            <button wire:click="avvia({{ $lav->id }})" class="btn btn-xs btn-success">
              <i class="fas fa-play"></i> Avvia
            </button>
            @elseif($lav->is_attiva)
            <button wire:click="ferma({{ $lav->id }})" class="btn btn-xs btn-danger">
              <i class="fas fa-stop"></i> Ferma
            </button>
            @else
            <span class="text-muted small">—</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" class="text-muted text-center">Nessuna lavorazione registrata.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Riepilogo totali -->
  <div class="row mt-3">
    <div class="col-md-8 offset-md-4">
      <table class="table table-sm table-bordered">
        <tr>
          <th>Totale min. preventivati</th>
          <td class="text-right">{{ $totMinPreventivati }} min ({{ number_format($totMinPreventivati / 60, 1) }} h)</td>
        </tr>
        <tr>
          <th>Totale min. effettivi</th>
          <td class="text-right">{{ $totMinEffettivi }} min ({{ number_format($totMinEffettivi / 60, 1) }} h)</td>
        </tr>
        <tr>
          <th>Delta totale</th>
          <td class="text-right {{ $deltaMinuti > 0 ? 'text-danger' : 'text-success' }}">
            {{ $deltaMinuti >= 0 ? '+' : '' }}{{ $deltaMinuti }} min
          </td>
        </tr>
        <tr>
          <th>Ore fatturabili (da preventivo)</th>
          <td class="text-right">{{ number_format($oreFatturabili, 1) }} h</td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Modal Nuova/Modifica Lavorazione -->
  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $lavorazioneId ? 'Modifica Lavorazione' : 'Nuova Lavorazione' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal', false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Descrizione <span class="text-danger">*</span></label>
            <input type="text" wire:model="descrizione" class="form-control @error('descrizione') is-invalid @enderror">
            @error('descrizione')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Minuti preventivati</label>
                <input type="number" wire:model="minuti_preventivati" class="form-control" min="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Meccanico</label>
                <select wire:model="user_id" class="form-control @error('user_id') is-invalid @enderror">
                  <option value="">— seleziona —</option>
                  @foreach($meccanici as $m)
                  <option value="{{ $m->id }}">{{ $m->name }}</option>
                  @endforeach
                </select>
                @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Ponte</label>
                <select wire:model="ponte_id" class="form-control">
                  <option value="">— nessuno —</option>
                  @foreach($ponti as $p)
                  <option value="{{ $p->id }}">{{ $p->nome }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Riga preventivo collegata</label>
                <select wire:model="commessa_riga_id" class="form-control">
                  <option value="">— nessuna —</option>
                  @foreach($righe as $r)
                  <option value="{{ $r->id }}">{{ $r->descrizione }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea wire:model="note" class="form-control" rows="2"></textarea>
          </div>
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
</div>
