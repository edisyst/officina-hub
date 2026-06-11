<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtro periodo --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <div class="input-group-prepend"><span class="input-group-text">Periodo</span></div>
                <select wire:model.live="periodoMesi" class="form-control">
                    <option value="3">Ultimi 3 mesi</option>
                    <option value="6">Ultimi 6 mesi</option>
                    <option value="12">Ultimi 12 mesi</option>
                    <option value="24">Ultimi 24 mesi</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Metriche principali --}}
    <div class="row">
        <div class="col-md-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($metriche['nuovi']) }}</h3>
                    <p>Clienti nuovi nel periodo</p>
                </div>
                <div class="icon"><i class="fas fa-user-plus"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($metriche['persi']) }}</h3>
                    <p>Clienti persi nel periodo</p>
                </div>
                <div class="icon"><i class="fas fa-user-times"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $metriche['tassoRitorno'] }}%</h3>
                    <p>Tasso di ritorno (≥2 visite)</p>
                </div>
                <div class="icon"><i class="fas fa-redo"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>€ {{ number_format($metriche['valoreLifetimeMedio'], 0, ',', '.') }}</h3>
                    <p>Valore lifetime medio</p>
                </div>
                <div class="icon"><i class="fas fa-euro-sign"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Grafico distribuzione segmenti --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Composizione clientela</h3></div>
                <div class="card-body">
                    <canvas id="chartSegmenti" height="220"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafico nuovi clienti per mese --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Nuovi clienti per mese (ultimi 12 mesi)</h3></div>
                <div class="card-body">
                    <canvas id="chartNuovi" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Ticket medio per segmento --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Ticket medio per segmento</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Segmento</th>
                                <th class="text-right">Clienti</th>
                                <th class="text-right">Valore totale</th>
                                <th class="text-right">Ticket medio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($metriche['ticketPerSegmento'] as $seg)
                            <tr>
                                <td><span class="badge {{ $seg['badge'] }}">{{ $seg['label'] }}</span></td>
                                <td class="text-right">{{ number_format($seg['count']) }}</td>
                                <td class="text-right">€ {{ number_format($seg['valore_totale'], 2, ',', '.') }}</td>
                                <td class="text-right">€ {{ number_format($seg['ticket_medio'], 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista clienti da recuperare --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Top 20 clienti da recuperare (segmento "A rischio")</h3>
                </div>
                <div class="card-body p-0">
                    @if ($daRecuperare->isEmpty())
                        <p class="p-3 text-muted">Nessun cliente in segmento "a rischio".</p>
                    @else
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Ultima visita</th>
                                    <th class="text-right">Valore lifetime</th>
                                    <th class="text-right">N. visite</th>
                                    <th>Veicoli</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daRecuperare as $cliente)
                                <tr>
                                    <td>
                                        <a href="{{ route('clienti.show', $cliente->id) }}">{{ $cliente->nome_completo }}</a>
                                    </td>
                                    <td>{{ $cliente->ultima_visita_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-right">€ {{ number_format($cliente->valore_lifetime, 2, ',', '.') }}</td>
                                    <td class="text-right">{{ $cliente->numero_visite }}</td>
                                    <td>
                                        @foreach ($cliente->veicoli->take(2) as $v)
                                            <span class="badge badge-secondary">{{ $v->targa }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <a href="{{ route('clienti.show', $cliente->id) }}#tab-crm"
                                           class="btn btn-xs btn-outline-warning">
                                            <i class="fas fa-envelope"></i> Contatta
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var distribuzione = @json($distribuzione);
    var nuoviPerMese  = @json($nuoviPerMese);

    // Doughnut segmenti
    new Chart(document.getElementById('chartSegmenti').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: distribuzione.map(function(d) {
                return d.label + ' (' + d.valore + ')';
            }),
            datasets: [{
                data: distribuzione.map(function(d) { return d.valore; }),
                backgroundColor: distribuzione.map(function(d) { return d.colore; }),
            }]
        },
        options: {
            responsive: true,
            legend: { position: 'bottom' }
        }
    });

    // Bar nuovi clienti
    new Chart(document.getElementById('chartNuovi').getContext('2d'), {
        type: 'bar',
        data: {
            labels: nuoviPerMese.labels,
            datasets: [{
                label: 'Nuovi clienti',
                data: nuoviPerMese.valori,
                backgroundColor: 'rgba(23,162,184,0.7)',
            }]
        },
        options: {
            responsive: true,
            scales: { yAxes: [{ ticks: { beginAtZero: true, stepSize: 1 } }] }
        }
    });
})();
</script>
@endpush
