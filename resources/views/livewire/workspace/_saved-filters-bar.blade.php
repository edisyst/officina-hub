{{--
  Saved-filters bar — include in list components that use WithSavedFilters.
  Needs: $savedFiltersData, $newFilterName, $showSaveFilterModal
--}}
<div class="d-flex align-items-center mb-2" style="gap:.5rem;flex-wrap:wrap">
  @if(!empty($savedFiltersData))
  <div class="dropdown">
    <button class="btn btn-xs btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
      <i class="fas fa-filter mr-1"></i>Filtri salvati
    </button>
    <div class="dropdown-menu" style="min-width:220px">
      @foreach($savedFiltersData as $sf)
      <div class="dropdown-item d-flex align-items-center justify-content-between p-1 px-2">
        <button
          class="btn btn-link btn-sm p-0 text-left flex-grow-1"
          wire:click="applySavedFilter({{ $sf['id'] }})"
          style="text-decoration:none"
        >
          @if($sf['is_default'])<i class="fas fa-check-circle text-success mr-1" title="Default"></i>@endif
          {{ $sf['name'] }}
        </button>
        <div class="d-flex ml-1" style="gap:.2rem">
          <button
            class="btn btn-xs {{ $sf['is_default'] ? 'btn-success' : 'btn-outline-secondary' }}"
            wire:click="{{ $sf['is_default'] ? 'clearDefaultFilter' : "setDefaultFilter({$sf['id']})" }}"
            title="{{ $sf['is_default'] ? 'Rimuovi default' : 'Imposta default' }}"
          ><i class="fas fa-home" style="font-size:10px"></i></button>
          <button
            class="btn btn-xs btn-outline-danger"
            wire:click="deleteSavedFilter({{ $sf['id'] }})"
            wire:confirm="Eliminare il filtro '{{ $sf['name'] }}'?"
          ><i class="fas fa-times" style="font-size:10px"></i></button>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  <button class="btn btn-xs btn-outline-primary" wire:click="$set('showSaveFilterModal', true)">
    <i class="fas fa-save mr-1"></i>Salva filtri correnti
  </button>

  @if($showSaveFilterModal)
  <div class="d-inline-flex align-items-center" style="gap:.25rem">
    <input
      type="text"
      class="form-control form-control-sm"
      wire:model="newFilterName"
      wire:keydown.enter="saveCurrentFilters"
      wire:keydown.escape="$set('showSaveFilterModal', false)"
      placeholder="Nome filtro..."
      style="width:160px"
      autofocus
    >
    <button class="btn btn-xs btn-primary" wire:click="saveCurrentFilters">
      <i class="fas fa-check"></i>
    </button>
    <button class="btn btn-xs btn-secondary" wire:click="$set('showSaveFilterModal', false)">
      <i class="fas fa-times"></i>
    </button>
  </div>
  @endif

  @error('newFilterName')<span class="text-danger small ml-1">{{ $message }}</span>@enderror
</div>
