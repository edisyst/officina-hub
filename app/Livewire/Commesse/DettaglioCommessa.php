<?php

namespace App\Livewire\Commesse;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Actions\Fatturazione\GeneraFatturaDoppiaAction;
use App\Actions\Fatturazione\GeneraFatturaGaranziaAction;
use App\Actions\Scadenze\CreaScadenzeAutomaticheAction;
use App\Enums\StatoCarrozzeria;
use App\Enums\StatoCommessa;
use App\Enums\TipoCommessa;
use App\Models\Commessa;
use App\Models\Documento;
use App\Services\MarginalitaService;
use Livewire\Attributes\On;
use Livewire\Component;

class DettaglioCommessa extends Component
{
    public Commessa $commessa;
    public string $tabAttiva = 'lavorazioni';
    public array $marginalita = [];

    // Modal transizione stato
    public bool $showTransizioneModal = false;
    public ?string $statoTarget = null;
    public string $notaTransizione = '';

    // Modal firma
    public bool $showFirmaModal = false;
    public string $tipoFirma = 'cliente'; // cliente | consegna
    public string $firmasvg = '';

    // Modal scadenze suggerite
    public bool $showScadenzeSuggerite = false;
    public array $suggerimentiScadenze = [];
    public array $tipiScadenzeSelezionati = [];

    // Carrozzeria
    public string $subTabCarrozzeria = 'sinistro';
    public bool $showConfermaAvanzaFase = false;
    public bool $showDoppiaFatturaModal  = false;
    public array $previewDoppiaFattura  = [];

    // Garanzia
    public bool $showFatturaGaranziaModal = false;
    public array $previewFatturaGaranzia  = [];

    // Stato accettazione veicolo (SVG interattivo)
    public bool $showStatoAccettazioneModal = false;
    public array $noteAccettazione = [];

    public function mount(int|string $commessaId): void
    {
        $this->commessa = Commessa::with(['cliente', 'veicolo', 'user', 'righe', 'allegati', 'log.user'])
            ->findOrFail($commessaId);

        if (auth()->user()->hasAnyRole(['admin', 'cassa'])) {
            $this->marginalita = app(MarginalitaService::class)->calcola($this->commessa);
        }

        if ($this->commessa->tipo === TipoCommessa::Carrozzeria) {
            $this->noteAccettazione = $this->commessa->note_accettazione_json ?? [];
        }
    }

    public function apriTransizione(string $stato): void
    {
        $this->statoTarget = $stato;
        $this->notaTransizione = '';

        // La consegna richiede la firma: mostra prima il modal firma
        if ($stato === StatoCommessa::Consegnata->value) {
            $this->tipoFirma = 'consegna';
            $this->firmasvg = '';
            $this->showFirmaModal = true;
            return;
        }

        // L'accettazione può richiedere la firma cliente se non già acquisita
        if ($stato === StatoCommessa::Accettata->value && ! $this->commessa->firma_cliente_svg) {
            $this->tipoFirma = 'cliente';
            $this->firmasvg = '';
            $this->showFirmaModal = true;
            return;
        }

        $this->showTransizioneModal = true;
    }

    public function salvaFirma(): void
    {
        if (empty($this->firmasvg)) {
            $this->addError('firmasvg', 'La firma è obbligatoria.');
            return;
        }

        $campo = $this->tipoFirma === 'consegna' ? 'firma_consegna_svg' : 'firma_cliente_svg';
        $this->commessa->update([$campo => $this->firmasvg]);

        $this->showFirmaModal = false;

        // Prosegui con la transizione
        $this->showTransizioneModal = true;
    }

    public function eseguiTransizione(): void
    {
        // Sospensione richiede nota obbligatoria
        if ($this->statoTarget === StatoCommessa::Sospesa->value && empty($this->notaTransizione)) {
            $this->addError('notaTransizione', 'Il motivo della sospensione è obbligatorio.');
            return;
        }

        $nuovoStato = StatoCommessa::from($this->statoTarget);

        // Autorizzazione contestuale
        $abilita = match($nuovoStato) {
            StatoCommessa::Accettata => 'accetta',
            StatoCommessa::InLavorazione => $this->commessa->stato === StatoCommessa::Accettata ? 'avviaLavori' : 'riprendi',
            StatoCommessa::Sospesa => 'sospendi',
            StatoCommessa::Completata => 'completa',
            StatoCommessa::Consegnata => 'consegna',
            StatoCommessa::Fatturata => 'fattura',
            default => null,
        };

        if ($abilita) {
            $this->authorize($abilita, $this->commessa);
        }

        // Per le commesse di carrozzeria, "completata" richiede stato_carrozzeria = consegna
        if ($nuovoStato === StatoCommessa::Completata
            && $this->commessa->tipo === TipoCommessa::Carrozzeria
            && $this->commessa->stato_carrozzeria !== StatoCarrozzeria::Consegna) {
            $this->addError('notaTransizione', 'Per completare una commessa di carrozzeria tutte le fasi devono essere concluse (ultima fase: Consegna).');
            return;
        }

        app(AggiornaStatoAction::class)->execute(
            $this->commessa,
            $nuovoStato,
            auth()->user(),
            $this->notaTransizione ?: null,
        );

        $this->commessa->refresh();
        $this->commessa->load(['cliente', 'veicolo', 'user', 'righe', 'allegati', 'log.user']);

        $this->showTransizioneModal = false;
        $this->statoTarget = null;
        session()->flash('success', "Stato aggiornato: {$nuovoStato->label()}");
    }

    #[On('scadenze-suggerite')]
    public function onScadenzeSuggerite(int $commessa_id, array $suggerimenti): void
    {
        // Risponde solo all'evento della propria commessa
        if ($commessa_id !== $this->commessa->id) return;

        $this->suggerimentiScadenze     = $suggerimenti;
        $this->tipiScadenzeSelezionati  = array_column($suggerimenti, 'tipo');
        $this->showScadenzeSuggerite    = true;
    }

    public function confermaSuggerimentiScadenze(): void
    {
        if (! empty($this->tipiScadenzeSelezionati)) {
            $suggerimentiConCarbon = array_map(fn($s) => array_merge($s, [
                'data_scadenza' => \Carbon\Carbon::parse($s['data_scadenza']),
                'tipo'          => \App\Enums\TipoScadenza::from($s['tipo']),
            ]), $this->suggerimentiScadenze);

            app(CreaScadenzeAutomaticheAction::class)->salva(
                $this->commessa,
                $suggerimentiConCarbon,
                $this->tipiScadenzeSelezionati,
            );
        }

        $this->showScadenzeSuggerite   = false;
        $this->suggerimentiScadenze    = [];
        $this->tipiScadenzeSelezionati = [];
    }

    // ─── Carrozzeria: avanzamento fase ────────────────────────────────────────

    public function avanzaFaseCarrozzeria(): void
    {
        $this->authorize('update', $this->commessa);

        $corrente = $this->commessa->stato_carrozzeria;
        $prossimo = $corrente ? $corrente->successiva() : StatoCarrozzeria::Accettazione;

        if ($prossimo === null) {
            return;
        }

        $this->commessa->update(['stato_carrozzeria' => $prossimo]);
        $this->commessa->refresh();

        $this->showConfermaAvanzaFase = false;
        session()->flash('success', "Fase carrozzeria avanzata: {$prossimo->label()}");
    }

    // ─── Carrozzeria: stato accettazione veicolo ───────────────────────────────

    public function salvaStatoAccettazione(): void
    {
        $this->authorize('update', $this->commessa);
        $this->commessa->update(['note_accettazione_json' => $this->noteAccettazione]);
        $this->commessa->refresh();
        $this->showStatoAccettazioneModal = false;
        session()->flash('success', 'Stato accettazione veicolo salvato.');
    }

    public function toggleZonaAccettazione(string $zona): void
    {
        if (!isset($this->noteAccettazione[$zona])) {
            $this->noteAccettazione[$zona] = ['presente' => true, 'nota' => ''];
        } else {
            unset($this->noteAccettazione[$zona]);
        }
    }

    // ─── Carrozzeria: doppia fattura ────────────────────────────────────────

    public function apriDoppiaFattura(): void
    {
        $this->authorize('create', Documento::class);

        $this->commessa->load(['sinistro.perizia', 'sinistro.compagniaAssicurativa']);
        $sinistro = $this->commessa->sinistro;
        $perizia  = $sinistro?->perizia;

        if (!$perizia || $perizia->importo_netto_liquidato === null) {
            session()->flash('error', 'Perizia non disponibile o importo netto non valorizzato.');
            return;
        }

        $netto   = (float) $perizia->importo_netto_liquidato;
        $ivaRate = $this->commessa->totale_imponibile > 0
            ? $this->commessa->totale_iva / $this->commessa->totale_imponibile
            : 0.22;

        $this->previewDoppiaFattura = [
            'totale_commessa'   => $this->commessa->totale_lordo,
            'imp_assicurazione' => $netto,
            'iva_assicurazione' => round($netto * $ivaRate, 2),
            'tot_assicurazione' => round($netto * (1 + $ivaRate), 2),
            'imp_cliente'       => round($this->commessa->totale_imponibile - $netto, 2),
            'iva_cliente'       => round($this->commessa->totale_iva - $netto * $ivaRate, 2),
            'tot_cliente'       => round($this->commessa->totale_lordo - $netto * (1 + $ivaRate), 2),
            'sinistro_numero'   => $sinistro->numero_sinistro,
            'compagnia'         => $sinistro->compagniaAssicurativa?->nome,
        ];

        $this->showDoppiaFatturaModal = true;
    }

    public function generaDoppiaFattura(): void
    {
        $this->authorize('create', Documento::class);

        try {
            [$docCliente, $docAssicurazione] = app(GeneraFatturaDoppiaAction::class)->execute($this->commessa);
            $this->commessa->refresh();
            $this->showDoppiaFatturaModal = false;
            session()->flash('success', "Doppia fattura generata: {$docCliente->numero} (cliente) + {$docAssicurazione->numero} (assicurazione).");
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ─── Garanzia: fattura ────────────────────────────────────────────────────

    public function apriGaranziaFattura(): void
    {
        $this->authorize('create', Documento::class);

        $this->commessa->load('righe.casaMadre');

        $righeGaranzia = $this->commessa->righe->where('in_garanzia', true);
        $righeCliente  = $this->commessa->righe->where('in_garanzia', false);

        $perCasaMadre = $righeGaranzia->groupBy('casa_madre_id')->map(function ($righe) {
            $cm = $righe->first()->casaMadre;
            return [
                'ragione_sociale' => $cm?->ragione_sociale ?? 'Garanzia interna',
                'totale'          => $righe->sum(fn($r) => $r->totale),
                'count_righe'     => $righe->count(),
            ];
        })->values()->toArray();

        $this->previewFatturaGaranzia = [
            'tot_cliente'    => $this->commessa->righe->sum(fn($r) => $r->totale_cliente),
            'tot_case_madri' => $this->commessa->righe->sum(fn($r) => $r->totale_casa_madre),
            'per_casa_madre' => $perCasaMadre,
            'righe_cliente'  => $righeCliente->count(),
        ];

        $this->showFatturaGaranziaModal = true;
    }

    public function generaFatturaGaranzia(): void
    {
        $this->authorize('create', Documento::class);

        try {
            $documenti = app(GeneraFatturaGaranziaAction::class)->execute($this->commessa);
            $this->commessa->refresh();
            $this->showFatturaGaranziaModal = false;

            $numeri = collect($documenti)->pluck('numero')->implode(' + ');
            session()->flash('success', "Fatture garanzia generate: {$numeri}.");
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $garanzieAttive = $this->commessa->veicolo
            ? $this->commessa->veicolo->garanzie()->attive()->with('casaMadre')->get()
            : collect();

        return view('livewire.commesse.dettaglio-commessa', [
            'marginalita'     => $this->marginalita,
            'fasiCarrozzeria' => StatoCarrozzeria::inOrdine(),
            'garanzieAttive'  => $garanzieAttive,
        ]);
    }
}
