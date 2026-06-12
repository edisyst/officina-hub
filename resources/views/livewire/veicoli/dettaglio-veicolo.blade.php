<div>
  <div class="row">
    <div class="col-md-5">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-{{ $veicolo->tipo->value === 'moto' ? 'motorcycle' : 'car' }} mr-2"></i>
            {{ $veicolo->targa ?? 'Senza Targa' }}
          </h3>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5">Marca/Modello</dt>
            <dd class="col-7">{{ $veicolo->marca }} {{ $veicolo->modello }} {{ $veicolo->versione }}</dd>
            @if($veicolo->vin)
            <dt class="col-5">Telaio (VIN)</dt><dd class="col-7">{{ $veicolo->vin }}</dd>
            @endif
            <dt class="col-5">Alimentazione</dt><dd class="col-7">{{ $veicolo->alimentazione->label() }}</dd>
            @if($veicolo->cilindrata)
            <dt class="col-5">Cilindrata</dt><dd class="col-7">{{ $veicolo->cilindrata }} cc</dd>
            @endif
            @if($veicolo->anno_immatricolazione)
            <dt class="col-5">Anno</dt><dd class="col-7">{{ $veicolo->anno_immatricolazione }}</dd>
            @endif
            @if($veicolo->colore)
            <dt class="col-5">Colore</dt><dd class="col-7">{{ $veicolo->colore }}</dd>
            @endif
            @if($veicolo->km_attuali)
            <dt class="col-5">KM Attuali</dt><dd class="col-7">{{ number_format($veicolo->km_attuali) }} km</dd>
            @endif
          </dl>
        </div>
        <div class="card-footer">
          <a href="{{ route('veicoli.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Torna alla lista
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      {{-- Tab navigation --}}
      <ul class="nav nav-tabs" id="veicoloTabs">
        <li class="nav-item">
          <a href="#" wire:click.prevent="$set('tabAttiva', 'storico')"
             class="nav-link {{ $tabAttiva === 'storico' ? 'active' : '' }}">
            <i class="fas fa-history mr-1"></i>Storico
          </a>
        </li>
        <li class="nav-item">
          <a href="#" wire:click.prevent="$set('tabAttiva', 'pneumatici')"
             class="nav-link {{ $tabAttiva === 'pneumatici' ? 'active' : '' }}">
            <i class="fas fa-circle-notch mr-1"></i>Pneumatici
            @if($countPneumaticiDeposito > 0)
              <span class="badge badge-primary ml-1">{{ $countPneumaticiDeposito }}</span>
            @endif
          </a>
        </li>
        <li class="nav-item">
          <a href="#" wire:click.prevent="$set('tabAttiva', 'garanzie')"
             class="nav-link {{ $tabAttiva === 'garanzie' ? 'active' : '' }}">
            <i class="fas fa-shield-alt mr-1"></i>Garanzie
            @if($countGaranzieAttive > 0)
              <span class="badge badge-success ml-1">{{ $countGaranzieAttive }}</span>
            @endif
          </a>
        </li>
      </ul>

      <div class="tab-content border border-top-0 p-3 bg-white">
        @if($tabAttiva === 'storico')
        {{-- Storico proprietari --}}
        <div class="card mb-2">
          <div class="card-header p-2"><h3 class="card-title">Storico Proprietari</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Cliente</th><th>Dal</th><th>Al</th><th>Stato</th></tr></thead>
              <tbody>
                @forelse($proprietari as $p)
                <tr>
                  <td><a href="{{ route('clienti.show', $p->id) }}">{{ $p->nome_completo }}</a></td>
                  <td>{{ $p->pivot->data_inizio ? \Carbon\Carbon::parse($p->pivot->data_inizio)->format('d/m/Y') : '-' }}</td>
                  <td>{{ $p->pivot->data_fine ? \Carbon\Carbon::parse($p->pivot->data_fine)->format('d/m/Y') : '-' }}</td>
                  <td>
                    @if($p->pivot->proprietario_attuale)
                    <span class="badge badge-success">Attuale</span>
                    @else
                    <span class="badge badge-secondary">Ex proprietario</span>
                    @endif
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">Nessun proprietario registrato.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- Commesse --}}
        <div class="card">
          <div class="card-header p-2"><h3 class="card-title">Commesse</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Numero</th><th>Tipo</th><th>Stato</th><th>Ingresso</th></tr></thead>
              <tbody>
                @forelse($commesse as $c)
                <tr>
                  <td><a href="{{ route('commesse.show', $c->id) }}">{{ $c->numero }}</a></td>
                  <td>{{ $c->tipo->label() }}</td>
                  <td><span class="badge {{ $c->stato->badgeClass() }}">{{ $c->stato->label() }}</span></td>
                  <td>{{ $c->data_ingresso->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">Nessuna commessa.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @endif

        @if($tabAttiva === 'pneumatici')
        @livewire('pneumatici.gestione-pneumatici', ['veicoloId' => $veicolo->id], key('pneumatici-' . $veicolo->id))
        @endif

        @if($tabAttiva === 'garanzie')
        {{-- Allerta garanzie in scadenza --}}
        @foreach($garanzieInScadenza as $g)
        <div class="alert alert-warning py-2 mb-2">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          Garanzia in scadenza: <strong>{{ $g->descrizione }}</strong>
          — scade il {{ $g->data_fine->format('d/m/Y') }}
          (tra {{ $g->data_fine->diffInDays(now()) }} giorni)
        </div>
        @endforeach
        @livewire('garanzie.gestione-garanzie', ['veicoloId' => $veicolo->id], key('garanzie-' . $veicolo->id))
        @endif
      </div>
    </div>
  </div>
</div>
