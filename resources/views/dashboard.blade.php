<x-app-layout>
  <x-slot name="title">Dashboard</x-slot>

  <livewire:dashboard.stato-officina />

  <div class="row mt-3">
    <div class="col-lg-3 col-6">
      <div class="small-box bg-info">
        <div class="inner">
          <h3>{{ $commesseAperte }}</h3>
          <p>Commesse Aperte</p>
        </div>
        <div class="icon"><i class="fas fa-clipboard-list"></i></div>
        <a href="{{ route('commesse.index') }}" class="small-box-footer">Vedi tutte <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="small-box bg-success">
        <div class="inner">
          <h3>{{ $commessePronte }}</h3>
          <p>Pronte per Consegna</p>
        </div>
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <a href="{{ route('commesse.index') }}" class="small-box-footer">Dettagli <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="small-box bg-warning">
        <div class="inner">
          <h3>{{ $totaleClienti }}</h3>
          <p>Clienti Attivi</p>
        </div>
        <div class="icon"><i class="fas fa-users"></i></div>
        <a href="{{ route('clienti.index') }}" class="small-box-footer">Vedi tutti <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="small-box bg-danger">
        <div class="inner">
          <h3>{{ $totaleVeicoli }}</h3>
          <p>Veicoli Registrati</p>
        </div>
        <div class="icon"><i class="fas fa-car"></i></div>
        <a href="{{ route('veicoli.index') }}" class="small-box-footer">Vedi tutti <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Ultime commesse</h3>
          @can('create', \App\Models\Commessa::class)
          <div class="card-tools">
            <a href="{{ route('commesse.create') }}" class="btn btn-sm btn-primary">
              <i class="fas fa-plus"></i> Nuova commessa
            </a>
          </div>
          @endcan
        </div>
        <div class="card-body p-0">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Numero</th><th>Cliente</th><th>Veicolo</th><th>Stato</th><th>Ingresso</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ultimeCommesse as $c)
              <tr>
                <td><a href="{{ route('commesse.show', $c->id) }}">{{ $c->numero }}</a></td>
                <td>{{ $c->cliente->nome_completo }}</td>
                <td>{{ $c->veicolo->targa ?? '-' }} — {{ $c->veicolo->marca }} {{ $c->veicolo->modello }}</td>
                <td><span class="badge {{ $c->stato->badgeClass() }}">{{ $c->stato->label() }}</span></td>
                <td>{{ $c->data_ingresso->format('d/m/Y') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Commesse per stato</h3>
        </div>
        <div class="card-body">
          @foreach(\App\Enums\StatoCommessa::cases() as $stato)
          <div class="d-flex justify-content-between mb-2">
            <span class="badge {{ $stato->badgeClass() }} p-2">{{ $stato->label() }}</span>
            <strong>{{ $conteggioPerStato[$stato->value] ?? 0 }}</strong>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
