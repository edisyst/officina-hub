<?php

namespace App\Livewire\Magazzino;

use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\CategoriaArticolo;
use App\Models\MovimentoMagazzino;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportMagazzino extends Component
{
    public string $tabAttiva = 'inventario';

    // Dati grafico valore per categoria (passati via JSON ad Alpine)
    public array $graficoCategorie = [];

    // Filtri movimenti per periodo
    public string $dataDa = '';
    public string $dataA = '';
    public string $filtroTipoMovimento = '';

    public function mount(): void
    {
        $this->dataDa = now()->startOfMonth()->format('Y-m-d');
        $this->dataA  = now()->format('Y-m-d');
    }

    public function esportaCsv(): Response
    {
        $query = MovimentoMagazzino::with(['articolo', 'commessa', 'user'])
            ->when($this->dataDa, fn($q) => $q->where('created_at', '>=', $this->dataDa))
            ->when($this->dataA, fn($q) => $q->where('created_at', '<=', $this->dataA . ' 23:59:59'))
            ->when($this->filtroTipoMovimento, fn($q) => $q->where('tipo', $this->filtroTipoMovimento))
            ->orderBy('created_at');

        $movimenti = $query->get();

        $csvLines = [];
        $csvLines[] = implode(';', ['Data', 'Articolo', 'Codice', 'Tipo', 'Quantità', 'Prezzo unitario', 'Giacenza prec.', 'Giacenza succ.', 'Commessa', 'Documento', 'Utente', 'Note']);

        foreach ($movimenti as $m) {
            $csvLines[] = implode(';', [
                $m->created_at->format('d/m/Y H:i'),
                '"' . str_replace('"', '""', $m->articolo?->descrizione ?? '') . '"',
                $m->articolo?->codice ?? '',
                $m->tipo->label(),
                $m->quantita,
                number_format((float) $m->prezzo_unitario, 2, ',', ''),
                $m->giacenza_precedente,
                $m->giacenza_successiva,
                $m->commessa?->numero ?? '',
                $m->documento_fornitore ?? '',
                $m->user?->name ?? '',
                '"' . str_replace('"', '""', $m->note ?? '') . '"',
            ]);
        }

        $csv = "\xEF\xBB\xBF" . implode("\n", $csvLines);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="movimenti_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    public function render()
    {
        $inventario = Articolo::with(['categoria', 'fornitore'])
            ->attivi()
            ->orderBy('descrizione')
            ->get()
            ->map(function ($a) {
                $a->valore_acquisto = $a->giacenza_attuale * (float) $a->prezzo_acquisto;
                $a->valore_vendita  = $a->giacenza_attuale * (float) $a->prezzo_vendita;
                return $a;
            });

        $totaleAcquisto = $inventario->sum('valore_acquisto');
        $totaleVendita  = $inventario->sum('valore_vendita');

        $movimentiPeriodo = MovimentoMagazzino::with(['articolo', 'commessa', 'user'])
            ->when($this->dataDa, fn($q) => $q->where('created_at', '>=', $this->dataDa))
            ->when($this->dataA, fn($q) => $q->where('created_at', '<=', $this->dataA . ' 23:59:59'))
            ->when($this->filtroTipoMovimento, fn($q) => $q->where('tipo', $this->filtroTipoMovimento))
            ->orderByDesc('created_at')
            ->paginate(30);

        $topConsumi = MovimentoMagazzino::select('articolo_id')
            ->selectRaw('SUM(quantita) as totale_scaricato')
            ->where('tipo', TipoMovimento::Scarico->value)
            ->where('created_at', '>=', now()->subMonth())
            ->groupBy('articolo_id')
            ->orderByDesc('totale_scaricato')
            ->limit(10)
            ->with('articolo')
            ->get();

        $tipiMovimento = TipoMovimento::cases();

        // Rotazione inventario
        $scarichi12mesi = MovimentoMagazzino::select('articolo_id')
            ->selectRaw('SUM(quantita) as totale_scaricato')
            ->where('tipo', TipoMovimento::Scarico->value)
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('articolo_id')
            ->pluck('totale_scaricato', 'articolo_id');

        $ultimoScarico = MovimentoMagazzino::select('articolo_id')
            ->selectRaw('MAX(created_at) as ultimo')
            ->where('tipo', TipoMovimento::Scarico->value)
            ->groupBy('articolo_id')
            ->pluck('ultimo', 'articolo_id');

        $rotazione = Articolo::with('categoria')->attivi()->get()->map(function ($art) use ($scarichi12mesi, $ultimoScarico) {
            $scaricato = (float) ($scarichi12mesi[$art->id] ?? 0);
            $giacenzaMedia = max((float) $art->giacenza_attuale, 0.1); // evita divisione per zero
            $indiceRotazione = round($scaricato / $giacenzaMedia, 1);
            $ultimoMov = $ultimoScarico[$art->id] ?? null;
            $giorniSenzaMov = $ultimoMov ? now()->diffInDays($ultimoMov) : 999;

            $classe = match(true) {
                $giorniSenzaMov > 90 => 'ferma',
                $indiceRotazione >= 12 => 'alta',
                $indiceRotazione >= 4  => 'media',
                default               => 'bassa',
            };

            $art->indice_rotazione = $indiceRotazione;
            $art->classe_rotazione = $classe;
            $art->giorni_senza_mov = $giorniSenzaMov < 999 ? $giorniSenzaMov : null;
            return $art;
        })->sortByDesc('indice_rotazione');

        // Valore per categoria (grafico)
        $valoriCategorie = Articolo::with('categoria')
            ->attivi()
            ->where('giacenza_attuale', '>', 0)
            ->get()
            ->groupBy(fn($a) => $a->categoria?->nome ?? 'Senza categoria')
            ->map(fn($arti) => round($arti->sum(fn($a) => (float) $a->giacenza_attuale * (float) $a->prezzo_acquisto), 2))
            ->sortDesc();

        $this->graficoCategorie = [
            'labels' => $valoriCategorie->keys()->values()->toArray(),
            'valori' => $valoriCategorie->values()->toArray(),
        ];

        return view('livewire.magazzino.report-magazzino', compact(
            'inventario', 'totaleAcquisto', 'totaleVendita',
            'movimentiPeriodo', 'topConsumi', 'tipiMovimento',
            'rotazione'
        ));
    }
}
