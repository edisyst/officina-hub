<div>
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  @if($generato && count($ordiniGenerati) > 0)
    <div class="alert alert-info">
      <i class="fas fa-info-circle mr-1"></i>
      {{ count($ordiniGenerati) }} ordini generati.
      <a href="{{ route('acquisti.ordini') }}" class="alert-link">Vai alla lista ordini</a>
    </div>
  @endif

  @if(empty($fornitoriConArticoli))
    <div class="alert alert-success">
      <i class="fas fa-check-circle mr-1"></i>
      Nessun articolo sotto scorta con fornitore preferenziale assegnato.
    </div>
  @else
  <form wire:submit="generaOrdini">
    @foreach($fornitoriConArticoli as $gruppo)
    <div class="card card-outline card-warning mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-truck mr-2"></i>{{ $gruppo['fornitore']->ragione_sociale }}
        </h5>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width:40px">
                <input type="checkbox" checked
                  @change="
                    @foreach($gruppo['articoli'] as $aid => $adati)
                    $wire.set('selezioni.{{ $gruppo['fid'] }}.{{ $aid }}.selezionato', $event.target.checked);
                    @endforeach
                  ">
              </th>
              <th>Codice</th>
              <th>Descrizione</th>
              <th class="text-right">Giacenza</th>
              <th class="text-right">Scorta min.</th>
              <th class="text-right">Scorta max.</th>
              <th style="width:100px">Q.tà da ordinare</th>
              <th class="text-right">Prezzo acq.</th>
            </tr>
          </thead>
          <tbody>
            @foreach($gruppo['articoli'] as $aid => $adati)
            <tr>
              <td>
                <input type="checkbox"
                  wire:model="selezioni.{{ $gruppo['fid'] }}.{{ $aid }}.selezionato">
              </td>
              <td>{{ $adati['codice'] }}</td>
              <td>{{ $adati['descrizione'] }}</td>
              <td class="text-right text-danger"><strong>{{ $adati['giacenza'] }}</strong></td>
              <td class="text-right">{{ $adati['scorta_min'] }}</td>
              <td class="text-right">{{ $adati['scorta_max'] }}</td>
              <td>
                <input type="number" min="1"
                  wire:model="selezioni.{{ $gruppo['fid'] }}.{{ $aid }}.quantita"
                  class="form-control form-control-sm">
              </td>
              <td class="text-right">
                @if($adati['prezzo_acq'])
                  € {{ number_format((float)$adati['prezzo_acq'], 2, ',', '.') }}
                @else
                  —
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endforeach

    <div class="mt-3">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-shopping-cart mr-1"></i> Genera ordini
      </button>
      <a href="{{ route('acquisti.ordini') }}" class="btn btn-secondary ml-2">
        <i class="fas fa-arrow-left mr-1"></i> Annulla
      </a>
    </div>
  </form>
  @endif
</div>
