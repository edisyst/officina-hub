<div>
  @if($recommendations->isNotEmpty())
  <div class="card card-warning card-outline mb-3">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-lightbulb mr-2 text-warning"></i>
        Da valutare
        <span class="badge badge-warning ml-2">{{ $recommendations->count() }}</span>
      </h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th style="width:90px">Fonte</th>
            <th>Titolo</th>
            <th style="width:110px">Scadenza / km</th>
            <th style="width:160px"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($recommendations as $rec)
          <tr>
            <td>
              @if($rec->source === 'declined')
                <span class="badge badge-danger" title="Declinato sull'OdL #{{ $rec->originWorkOrder?->numero }}">Declinato</span>
              @elseif($rec->source === 'deadline')
                <span class="badge badge-info">Scadenza</span>
              @else
                <span class="badge badge-secondary">Chilometraggio</span>
              @endif
            </td>
            <td>
              <strong>{{ $rec->title }}</strong>
              @if($rec->description)
                <br><small class="text-muted">{{ $rec->description }}</small>
              @endif
              @if($rec->source === 'declined' && $rec->originWorkOrder)
                <br><small class="text-muted">OdL #{{ $rec->originWorkOrder->numero }}</small>
              @endif
            </td>
            <td class="text-nowrap">
              @if($rec->due_date)
                <small>{{ $rec->due_date->format('d/m/Y') }}</small><br>
              @endif
              @if($rec->due_km)
                <small>{{ number_format($rec->due_km, 0, ',', '.') }} km</small>
              @endif
            </td>
            <td class="text-right">
              <button class="btn btn-xs btn-success mr-1" wire:click="addToWorkOrder({{ $rec->id }})"
                wire:confirm="Aggiungi '{{ addslashes($rec->title) }}' all'OdL?">
                <i class="fas fa-plus"></i> Aggiungi
              </button>
              <button class="btn btn-xs btn-secondary" wire:click="openDismiss({{ $rec->id }})">
                <i class="fas fa-times"></i> Ignora
              </button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  @if($showDismissModal)
  <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Ignora suggerimento</h5>
          <button type="button" class="close" wire:click="$set('showDismissModal', false)">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group mb-0">
            <label class="text-muted small">Motivo (opzionale)</label>
            <input type="text" class="form-control form-control-sm" wire:model="dismissReason"
              placeholder="Es. già eseguito altrove" maxlength="200">
          </div>
        </div>
        <div class="modal-footer py-2">
          <button class="btn btn-sm btn-secondary" wire:click="$set('showDismissModal', false)">Annulla</button>
          <button class="btn btn-sm btn-danger" wire:click="confirmDismiss">Ignora</button>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
