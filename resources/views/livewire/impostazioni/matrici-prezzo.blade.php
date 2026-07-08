<div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Matrici di prezzo ricambi</h6>
    <button wire:click="apriNuovo" class="btn btn-sm btn-primary">
      <i class="fas fa-plus"></i> Nuova matrice
    </button>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif

  <table class="table table-sm table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th class="text-center">Scaglioni</th>
        <th class="text-center">Stato</th>
        <th class="text-center">Default</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($matrici as $m)
      <tr>
        <td>
          {{ $m->nome }}
          @if($m->is_default)
            <span class="badge badge-success ml-1">Default</span>
          @endif
        </td>
        <td class="text-center">{{ $m->scaglioni_count }}</td>
        <td class="text-center">
          <button wire:click="toggleAttiva({{ $m->id }})"
            class="btn btn-xs {{ $m->is_attiva ? 'btn-success' : 'btn-secondary' }}">
            {{ $m->is_attiva ? 'Attiva' : 'Inattiva' }}
          </button>
        </td>
        <td class="text-center">
          @if(! $m->is_default)
          <button wire:click="impostaDefault({{ $m->id }})" class="btn btn-xs btn-outline-primary">
            Imposta default
          </button>
          @else
          <span class="text-success"><i class="fas fa-check-circle"></i></span>
          @endif
        </td>
        <td class="text-right">
          <button wire:click="apriModifica({{ $m->id }})" class="btn btn-xs btn-info">
            <i class="fas fa-edit"></i>
          </button>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" class="text-center text-muted">Nessuna matrice configurata.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{ $matrici->links() }}

  @if($showModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $matriceId ? 'Modifica matrice' : 'Nuova matrice' }}</h5>
          <button type="button" class="close" wire:click="$set('showModal',false)"><span>&times;</span></button>
        </div>
        <div class="modal-body">

          <div class="form-group">
            <label>Nome matrice *</label>
            <input wire:model="nome" type="text" class="form-control @error('nome') is-invalid @enderror">
            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="m_attiva" wire:model="is_attiva">
            <label class="custom-control-label" for="m_attiva">Matrice attiva</label>
          </div>

          <h6>Scaglioni
            <small class="text-muted font-weight-normal">— costo_da inclusivo, costo_a esclusivo; ultimo scaglione aperto</small>
          </h6>

          <table class="table table-sm table-bordered mb-2">
            <thead class="thead-light">
              <tr>
                <th>Costo da (€)</th>
                <th>Costo a (€) <small class="text-muted">vuoto = aperto</small></th>
                <th>Markup %</th>
                <th>Arrotondamento</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($scaglioni as $i => $s)
              <tr wire:key="scaglione-{{ $i }}">
                <td>
                  <input wire:model.live="scaglioni.{{ $i }}.costo_da" type="number" step="0.01" min="0"
                    class="form-control form-control-sm @error('scaglioni.'.$i.'.costo_da') is-invalid @enderror">
                  @error('scaglioni.'.$i.'.costo_da')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </td>
                <td>
                  <input wire:model.live="scaglioni.{{ $i }}.costo_a" type="number" step="0.01"
                    class="form-control form-control-sm @error('scaglioni.'.$i.'.costo_a') is-invalid @enderror"
                    placeholder="aperto">
                  @error('scaglioni.'.$i.'.costo_a')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </td>
                <td>
                  <input wire:model.live="scaglioni.{{ $i }}.markup_percent" type="number" step="0.01" min="0"
                    class="form-control form-control-sm @error('scaglioni.'.$i.'.markup_percent') is-invalid @enderror">
                  @error('scaglioni.'.$i.'.markup_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </td>
                <td>
                  <select wire:model.live="scaglioni.{{ $i }}.arrotondamento" class="form-control form-control-sm">
                    <option value="none">Nessuno</option>
                    <option value="0.10">0,10 €</option>
                    <option value="0.50">0,50 €</option>
                    <option value="1.00">1,00 €</option>
                  </select>
                </td>
                <td class="text-center">
                  @if(count($scaglioni) > 1)
                  <button type="button" wire:click="rimuoviScaglione({{ $i }})" class="btn btn-xs btn-danger">
                    <i class="fas fa-trash"></i>
                  </button>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>

          <button type="button" wire:click="aggiungiScaglione" class="btn btn-sm btn-outline-secondary mb-3">
            <i class="fas fa-plus"></i> Aggiungi scaglione
          </button>

          @error('scaglioni')<div class="alert alert-danger py-1 px-2">{{ $message }}</div>@enderror

          <!-- Anteprima live -->
          <div class="card card-body bg-light py-2">
            <div class="form-inline">
              <label class="mr-2">Anteprima: costo €</label>
              <input wire:model.live="anteprimaCosto" type="number" step="0.01" min="0"
                class="form-control form-control-sm mr-2" style="width:120px" placeholder="es. 37.50">
              @if($anteprimaRisultato)
                <span class="font-weight-bold text-primary">→ prezzo suggerito: {{ $anteprimaRisultato }}</span>
              @endif
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" wire:click="$set('showModal',false)" class="btn btn-secondary">Annulla</button>
          <button type="button" wire:click="salva" class="btn btn-primary">
            <span wire:loading wire:target="salva" class="spinner-border spinner-border-sm mr-1"></span>
            Salva
          </button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
