<div class="card card-outline card-primary mb-3">
  <div class="card-body py-2">
    <div class="row align-items-center">
      <div class="col-auto">
        <label class="mb-0 font-weight-bold">Periodo:</label>
      </div>
      <div class="col-auto">
        <select wire:model.live="periodo" class="form-control form-control-sm">
          <option value="questo_mese">Questo mese</option>
          <option value="mese_scorso">Mese scorso</option>
          <option value="questo_trimestre">Questo trimestre</option>
          <option value="questo_anno">Questo anno</option>
          <option value="personalizzato">Personalizzato</option>
        </select>
      </div>
      @if(isset($periodo) && $periodo === 'personalizzato')
      <div class="col-auto d-flex align-items-center">
        <input type="date" wire:model.defer="dataDa" class="form-control form-control-sm" style="width:150px">
        <span class="mx-2">—</span>
        <input type="date" wire:model.defer="dataA" class="form-control form-control-sm" style="width:150px">
        <button wire:click="applicaFiltroPersonalizzato" class="btn btn-sm btn-primary ml-2">
          <i class="fas fa-filter"></i> Applica
        </button>
      </div>
      @endif
      <div class="col-auto ml-auto">
        <span wire:loading class="text-muted small"><i class="fas fa-spinner fa-spin mr-1"></i>Aggiornamento...</span>
      </div>
    </div>
  </div>
</div>
