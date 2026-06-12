<?php

namespace App\Services\Analytics;

use App\Enums\StatoCommessa;
use App\Enums\StatoDocumento;
use App\Enums\TipoCommessa;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimento;
use App\Enums\TipoRiga;
use App\Models\Articolo;
use App\Models\Commessa;
use App\Models\Documento;
use App\Models\MovimentoMagazzino;
use App\Models\Scadenza;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KpiService
{
    private const STATI_FATTURA_VALIDI = [
        StatoDocumento::Emessa,
        StatoDocumento::InviataSdi,
        StatoDocumento::AccettataSdi,
        StatoDocumento::Pagata,
    ];

    /** Fatturato totale nel periodo (fatture non annullate) */
    public function fatturatoPeriodo(Carbon $da, Carbon $a): float
    {
        $key = 'kpi_fatturato_' . $da->format('Ymd') . '_' . $a->format('Ymd');
        return (float) Cache::remember($key, 600, fn() =>
            Documento::where('tipo', TipoDocumento::Fattura)
                ->whereIn('stato', array_map(fn($s) => $s->value, self::STATI_FATTURA_VALIDI))
                ->whereDate('data_emissione', '>=', $da->toDateString())
                ->whereDate('data_emissione', '<=', $a->toDateString())
                ->sum('totale')
        );
    }

    /** Numero di fatture nel periodo */
    public function numereFatturePeriodo(Carbon $da, Carbon $a): int
    {
        $key = 'kpi_num_fatture_' . $da->format('Ymd') . '_' . $a->format('Ymd');
        return (int) Cache::remember($key, 600, fn() =>
            Documento::where('tipo', TipoDocumento::Fattura)
                ->whereIn('stato', array_map(fn($s) => $s->value, self::STATI_FATTURA_VALIDI))
                ->whereDate('data_emissione', '>=', $da->toDateString())
                ->whereDate('data_emissione', '<=', $a->toDateString())
                ->count()
        );
    }

    /** Ticket medio (fatturato / numero fatture) */
    public function ticketMedio(Carbon $da, Carbon $a): float
    {
        $num = $this->numereFatturePeriodo($da, $a);
        if ($num === 0) {
            return 0.0;
        }
        return round($this->fatturatoPeriodo($da, $a) / $num, 2);
    }

    /** Calcola delta percentuale vs stesso periodo anno precedente */
    public function deltaVsAnnoPrecedente(float $valoreAttuale, Carbon $da, Carbon $a): float
    {
        $daAnnoScorso = $da->copy()->subYear();
        $aAnnoScorso  = $a->copy()->subYear();
        $valorePrecedente = $this->fatturatoPeriodo($daAnnoScorso, $aAnnoScorso);

        if ($valorePrecedente == 0) {
            return $valoreAttuale > 0 ? 100.0 : 0.0;
        }
        return round(($valoreAttuale - $valorePrecedente) / $valorePrecedente * 100, 1);
    }

    /** Delta ticket medio vs periodo precedente (stessa durata, immediatamente prima) */
    public function deltaTicketMedioPeriodoPrecedente(Carbon $da, Carbon $a): float
    {
        $durata = $da->diffInDays($a);
        $daPrec = $da->copy()->subDays($durata + 1);
        $aPrec  = $da->copy()->subDay();
        $attualeTicket  = $this->ticketMedio($da, $a);
        $precedenteTicket = $this->ticketMedio($daPrec, $aPrec);

        if ($precedenteTicket == 0) {
            return $attualeTicket > 0 ? 100.0 : 0.0;
        }
        return round(($attualeTicket - $precedenteTicket) / $precedenteTicket * 100, 1);
    }

    /** Dati sparkline fatturato (ultimi 12 mesi, somme mensili) */
    public function sparklineFatturato(): array
    {
        return Cache::remember('kpi_sparkline_fatturato', 600, function () {
            [$annoExpr, $meseExpr] = $this->datePartExpressions('data_emissione');
            $risultati = Documento::where('tipo', TipoDocumento::Fattura)
                ->whereIn('stato', self::STATI_FATTURA_VALIDI)
                ->where('data_emissione', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
                ->select(
                    DB::raw("{$annoExpr} as anno"),
                    DB::raw("{$meseExpr} as mese"),
                    DB::raw('SUM(totale) as totale')
                )
                ->groupBy(DB::raw($annoExpr), DB::raw($meseExpr))
                ->orderBy('anno')
                ->orderBy('mese')
                ->get()
                ->keyBy(fn($r) => $r->anno . '-' . str_pad($r->mese, 2, '0', STR_PAD_LEFT));

            $valori = [];
            for ($i = 11; $i >= 0; $i--) {
                $data = now()->subMonths($i);
                $chiave = $data->format('Y') . '-' . $data->format('m');
                $valori[] = (float) ($risultati[$chiave]?->totale ?? 0);
            }
            return $valori;
        });
    }

    /** Dati grafico fatturato mensile (barre, ultimi 12 mesi) */
    public function graficoFatturato(): array
    {
        return Cache::remember('kpi_grafico_fatturato', 600, function () {
            [$annoExpr, $meseExpr] = $this->datePartExpressions('data_emissione');
            $risultati = Documento::where('tipo', TipoDocumento::Fattura)
                ->whereIn('stato', self::STATI_FATTURA_VALIDI)
                ->where('data_emissione', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
                ->select(
                    DB::raw("{$annoExpr} as anno"),
                    DB::raw("{$meseExpr} as mese"),
                    DB::raw('SUM(totale) as totale')
                )
                ->groupBy(DB::raw($annoExpr), DB::raw($meseExpr))
                ->orderBy('anno')
                ->orderBy('mese')
                ->get()
                ->keyBy(fn($r) => $r->anno . '-' . str_pad($r->mese, 2, '0', STR_PAD_LEFT));

            $mesiIt = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
            $labels = [];
            $valori = [];
            $colori = [];
            $meseCorrente = now()->format('Y-m');

            for ($i = 11; $i >= 0; $i--) {
                $data   = now()->subMonths($i);
                $chiave = $data->format('Y') . '-' . $data->format('m');
                $labels[] = $mesiIt[(int)$data->format('m') - 1] . ' ' . $data->format('y');
                $valori[] = (float) ($risultati[$chiave]?->totale ?? 0);
                $colori[] = $chiave === $meseCorrente
                    ? 'rgba(40,167,69,0.85)'
                    : 'rgba(60,141,188,0.7)';
            }

            return compact('labels', 'valori', 'colori');
        });
    }

    /** Commesse aperte (accettata, in_lavorazione, sospesa) */
    public function commesseAperte(): array
    {
        $statiAperti = [StatoCommessa::Accettata, StatoCommessa::InLavorazione, StatoCommessa::Sospesa];

        $counts = Commessa::whereIn('stato', $statiAperti)
            ->select('tipo', 'stato', DB::raw('COUNT(*) as count'))
            ->groupBy('tipo', 'stato')
            ->get();

        $totale    = 0;
        $perTipo   = [];
        $perStato  = [];

        foreach ($counts as $row) {
            $totale += $row->count;
            $perTipo[$row->tipo->value] = ($perTipo[$row->tipo->value] ?? 0) + $row->count;
            $perStato[$row->stato->value] = ($perStato[$row->stato->value] ?? 0) + $row->count;
        }

        return [
            'totale'     => $totale,
            'per_tipo'   => $perTipo,
            'per_stato'  => $perStato,
            'meccanica'  => $perTipo[TipoCommessa::Meccanica->value] ?? 0,
            'carrozzeria'=> $perTipo[TipoCommessa::Carrozzeria->value] ?? 0,
            'tagliando'  => $perTipo[TipoCommessa::Tagliando->value] ?? 0,
        ];
    }

    /** Ore fatturabili e lavorate nel periodo */
    public function orePeriodo(Carbon $da, Carbon $a): array
    {
        // Ore fatturabili: righe manodopera di commesse completate nel periodo
        $oreFatturabili = (float) DB::table('commessa_righe')
            ->join('commesse', 'commesse.id', '=', 'commessa_righe.commessa_id')
            ->where('commessa_righe.tipo', TipoRiga::Manodopera->value)
            ->whereIn('commesse.stato', [
                StatoCommessa::Completata->value,
                StatoCommessa::Consegnata->value,
                StatoCommessa::Fatturata->value,
            ])
            ->whereBetween('commesse.data_consegna', [$da, $a])
            ->whereNull('commesse.deleted_at')
            ->sum('commessa_righe.quantita');

        // Ore lavorate: lavorazioni chiuse nel periodo
        $minutiLavorati = (int) DB::table('lavorazioni')
            ->whereNotNull('stopped_at')
            ->whereBetween('stopped_at', [$da, $a])
            ->sum('minuti_effettivi');

        $oreLavorate = round($minutiLavorati / 60, 2);
        $efficienza  = $oreLavorate > 0
            ? round($oreFatturabili / $oreLavorate * 100, 1)
            : 0.0;

        return [
            'ore_fatturabili' => round($oreFatturabili, 2),
            'ore_lavorate'    => $oreLavorate,
            'efficienza'      => $efficienza,
        ];
    }

    /** Top N clienti per fatturato nel periodo */
    public function topClienti(Carbon $da, Carbon $a, int $limit = 10): array
    {
        $key = 'kpi_top_clienti_' . $da->format('Ymd') . '_' . $a->format('Ymd') . '_' . $limit;
        return Cache::remember($key, 600, function () use ($da, $a, $limit) {
            return DB::table('documenti')
                ->join('clienti', 'clienti.id', '=', 'documenti.cliente_id')
                ->leftJoin('commesse', 'commesse.id', '=', 'documenti.commessa_id')
                ->where('documenti.tipo', TipoDocumento::Fattura->value)
                ->whereIn('documenti.stato', array_map(fn($s) => $s->value, self::STATI_FATTURA_VALIDI))
                ->whereDate('documenti.data_emissione', '>=', $da->toDateString())
                ->whereDate('documenti.data_emissione', '<=', $a->toDateString())
                ->whereNull('documenti.deleted_at')
                ->select(
                    'clienti.id',
                    DB::raw("CONCAT(COALESCE(clienti.nome,''), ' ', COALESCE(clienti.cognome,''), ' ', COALESCE(clienti.ragione_sociale,'')) as nome_completo"),
                    DB::raw('COUNT(DISTINCT documenti.id) as num_fatture'),
                    DB::raw('SUM(documenti.totale) as fatturato'),
                    DB::raw('SUM(documenti.totale)/COUNT(DISTINCT documenti.id) as ticket_medio'),
                    DB::raw('MAX(commesse.data_consegna) as ultima_visita')
                )
                ->groupBy('clienti.id', 'clienti.nome', 'clienti.cognome', 'clienti.ragione_sociale')
                ->orderByDesc('fatturato')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /** Articoli più consumati (scarichi) nel periodo */
    public function articoliPiuConsumati(Carbon $da, Carbon $a, int $limit = 10): array
    {
        $key = 'kpi_art_consumati_' . $da->format('Ymd') . '_' . $a->format('Ymd') . '_' . $limit;
        return Cache::remember($key, 600, function () use ($da, $a, $limit) {
            return DB::table('movimenti_magazzino')
                ->join('articoli', 'articoli.id', '=', 'movimenti_magazzino.articolo_id')
                ->leftJoin('categorie_articoli', 'categorie_articoli.id', '=', 'articoli.categoria_articolo_id')
                ->where('movimenti_magazzino.tipo', TipoMovimento::Scarico->value)
                ->whereBetween('movimenti_magazzino.created_at', [$da, $a])
                ->select(
                    'articoli.id',
                    'articoli.codice',
                    'articoli.descrizione',
                    DB::raw("COALESCE(categorie_articoli.nome, 'N/A') as categoria"),
                    DB::raw('SUM(movimenti_magazzino.quantita) as quantita_scaricata'),
                    DB::raw('SUM(movimenti_magazzino.quantita * movimenti_magazzino.prezzo_unitario) as valore_scaricato')
                )
                ->groupBy('articoli.id', 'articoli.codice', 'articoli.descrizione', 'categorie_articoli.nome')
                ->orderByDesc('quantita_scaricata')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /** Distribuzione commesse per tipo (doughnut chart) */
    public function distribuzioneCommesse(): array
    {
        $counts = Commessa::select('tipo', DB::raw('COUNT(*) as count'))
            ->groupBy('tipo')
            ->get();

        $labels = [];
        $valori = [];
        $colori = ['rgba(60,141,188,0.8)', 'rgba(243,156,18,0.8)', 'rgba(40,167,69,0.8)'];
        $i = 0;

        foreach ($counts as $row) {
            $labels[] = $row->tipo->label();
            $valori[]  = $row->count;
            $i++;
        }

        return compact('labels', 'valori', 'colori');
    }

    /** Widget operativi real-time (non dipendono dal filtro periodo) */
    public function widgetOperativi(): array
    {
        // Commesse in attesa da > 24h (bozza/accettata)
        $commesseAttesa = Commessa::whereIn('stato', [StatoCommessa::Bozza, StatoCommessa::Accettata])
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        // Appuntamenti di oggi
        $appuntamentiOggi = \App\Models\Appuntamento::whereDate('data_ora_inizio', today())->count();

        // Articoli sotto scorta
        $sottoScorta = Articolo::attivi()->sottoScorta()->count();

        return [
            'commesse_attesa'    => $commesseAttesa,
            'appuntamenti_oggi'  => $appuntamentiOggi,
            'sotto_scorta'       => $sottoScorta,
        ];
    }

    /** Invalida le cache KPI statiche (grafici e sparkline) */
    public static function invalidaCache(): void
    {
        Cache::forget('kpi_sparkline_fatturato');
        Cache::forget('kpi_grafico_fatturato');
        // Le cache per-periodo (kpi_fatturato_*, kpi_num_fatture_*, ecc.) scadono naturalmente
        // dopo 10 minuti (TTL configurato in Cache::remember). Per invalidazione completa
        // servono cache tag con driver Redis/Memcached.
    }

    /**
     * Restituisce le espressioni SQL per anno e mese compatibili con MySQL e SQLite.
     * @return array{0: string, 1: string}  [annoExpr, meseExpr]
     */
    private function datePartExpressions(string $colonna): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return [
                "CAST(strftime('%Y', {$colonna}) AS INTEGER)",
                "CAST(strftime('%m', {$colonna}) AS INTEGER)",
            ];
        }
        return ["YEAR({$colonna})", "MONTH({$colonna})"];
    }
}
