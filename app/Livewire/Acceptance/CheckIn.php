<?php

namespace App\Livewire\Acceptance;

use App\Actions\Acceptance\CheckInVehicleAction;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\PacchettoServizio;
use App\Models\Veicolo;
use App\Services\Acceptance\AcceptanceContextService;
use App\Services\LookupTarga\LookupTargaService;
use Livewire\Component;

class CheckIn extends Component
{
    public int $stadio = 1;

    // Stadio 1
    public string $targaInput = '';
    public array $matchParziali = [];

    // Veicolo
    public ?int $veicoloId = null;
    public bool $modoNuovoVeicolo = false;
    public ?int $clienteId = null;

    // Stadio 2A — veicolo esistente
    public ?int $km_attuali = null;

    // Stadio 2B — nuovo veicolo
    public string $nv_tipo = 'auto';
    public string $nv_marca = '';
    public string $nv_modello = '';
    public ?string $nv_vin = null;
    public ?int $nv_anno = null;
    public string $nv_alimentazione = 'benzina';
    public ?int $nv_km = null;

    // Cliente (2B)
    public string $searchCliente = '';
    public array $suggerimentiClienti = [];
    public ?int $clienteSelezionatoId = null;
    public bool $modoNuovoCliente = false;

    public string $nc_tipo = 'fisica';
    public ?string $nc_nome = null;
    public ?string $nc_cognome = null;
    public ?string $nc_ragione_sociale = null;
    public ?string $nc_telefono = null;
    public ?string $nc_email = null;
    public ?string $nc_codice_fiscale = null;
    public ?string $nc_partita_iva = null;

    // Stadio 3 — OdL
    public string $tipo = 'meccanica';
    public string $descrizione_cliente = '';
    public ?string $data_uscita_prevista = null;

    // Pacchetti
    public string $cercaPacchetto = '';
    public array $suggerimentiPacchetti = [];
    public ?int $pacchettoId = null;
    public array $righePreventivo = [];
    public float $totalePacchetto = 0;

    // Lookup
    public string $lookupMessaggio = '';
    public bool $lookupCaricamento = false;

    // Errore generale
    public string $erroreGenerale = '';

    // ---------------------------------------------------------------
    // Stadio 1
    // ---------------------------------------------------------------

    public function updatedTargaInput(): void
    {
        $targa = strtoupper(trim($this->targaInput));

        if (strlen($targa) < 2) {
            $this->matchParziali = [];
            return;
        }

        $risultati = Veicolo::where('targa', 'like', "%{$targa}%")
            ->with('clientePrincipale')
            ->limit(8)
            ->get();

        $esatto = $risultati->firstWhere('targa', $targa);
        if ($esatto) {
            $this->selezionaVeicoloEsistente($esatto->id);
            return;
        }

        $this->matchParziali = $risultati->map(fn($v) => [
            'id'      => $v->id,
            'targa'   => $v->targa,
            'desc'    => $v->descrizione,
            'cliente' => $v->clientePrincipale?->nome_completo ?? '—',
        ])->toArray();
    }

    public function selezionaVeicoloEsistente(int $id): void
    {
        $veicolo = Veicolo::with('clientePrincipale')->find($id);
        if (! $veicolo) {
            return;
        }

        $this->veicoloId          = $id;
        $this->targaInput         = $veicolo->targa ?? '';
        $this->km_attuali         = $veicolo->km_attuali;
        $this->clienteId          = $veicolo->cliente_id;
        $this->matchParziali      = [];
        $this->modoNuovoVeicolo   = false;
        $this->stadio             = 2;
    }

    public function proseguiNuovoVeicolo(): void
    {
        $this->modoNuovoVeicolo = true;
        $this->matchParziali    = [];
        $this->veicoloId        = null;
        $this->stadio           = 2;
    }

    // ---------------------------------------------------------------
    // Lookup targa (stadio 2B)
    // ---------------------------------------------------------------

    public function cercaTarga(): void
    {
        $service = app(LookupTargaService::class);
        if (! $service->isAbilitato()) {
            return;
        }

        $targa = strtoupper(trim($this->targaInput));
        if (empty($targa)) {
            return;
        }

        $this->lookupCaricamento = true;
        $this->lookupMessaggio   = '';
        $dati                    = $service->cerca($targa);
        $this->lookupCaricamento = false;

        if ($dati === null) {
            $this->lookupMessaggio = 'Targa non trovata o servizio non disponibile.';
            return;
        }

        if (! empty($dati['marca']))                  $this->nv_marca = $dati['marca'];
        if (! empty($dati['modello']))                $this->nv_modello = $dati['modello'];
        if (! empty($dati['anno_immatricolazione']))  $this->nv_anno = $dati['anno_immatricolazione'];
        if (! empty($dati['vin']))                    $this->nv_vin = $dati['vin'];
        if (! empty($dati['alimentazione']))          $this->nv_alimentazione = $this->mappaAlimentazione($dati['alimentazione']);
    }

    private function mappaAlimentazione(string $value): string
    {
        $map = [
            'benzina' => 'benzina', 'gasoline' => 'benzina',
            'diesel'  => 'diesel',  'gasolio'  => 'diesel',
            'ibrido'  => 'ibrido',  'hybrid'   => 'ibrido',
            'elettrico' => 'elettrico', 'electric' => 'elettrico',
            'gpl'     => 'gpl',     'lpg'      => 'gpl',
            'metano'  => 'metano',  'cng'      => 'metano',
        ];

        return $map[strtolower($value)] ?? 'benzina';
    }

    // ---------------------------------------------------------------
    // Cliente search (stadio 2B)
    // ---------------------------------------------------------------

    public function updatedSearchCliente(): void
    {
        if (strlen($this->searchCliente) < 2) {
            $this->suggerimentiClienti = [];
            return;
        }

        $this->suggerimentiClienti = Cliente::search($this->searchCliente)
            ->limit(6)
            ->get()
            ->map(fn($c) => [
                'id'      => $c->id,
                'label'   => $c->nome_completo,
                'telefono'=> $c->telefono ?? '',
            ])
            ->toArray();
    }

    public function selezionaCliente(int $id): void
    {
        $this->clienteSelezionatoId  = $id;
        $c                           = Cliente::find($id);
        $this->searchCliente         = $c?->nome_completo ?? '';
        $this->suggerimentiClienti   = [];
        $this->modoNuovoCliente      = false;
    }

    public function resetCliente(): void
    {
        $this->clienteSelezionatoId = null;
        $this->searchCliente        = '';
        $this->suggerimentiClienti  = [];
    }

    // ---------------------------------------------------------------
    // Navigazione stadi
    // ---------------------------------------------------------------

    public function avanzaAlloStadio3(): void
    {
        $this->erroreGenerale = '';

        if ($this->modoNuovoVeicolo) {
            $errors = [];

            if (empty($this->nv_marca))   $errors['nv_marca']   = 'Marca obbligatoria.';
            if (empty($this->nv_modello)) $errors['nv_modello'] = 'Modello obbligatorio.';
            if (! in_array($this->nv_tipo, ['auto', 'moto'])) $errors['nv_tipo'] = 'Tipo non valido.';

            if ($this->modoNuovoCliente) {
                if ($this->nc_tipo === 'fisica') {
                    if (empty($this->nc_nome))    $errors['nc_nome']    = 'Nome obbligatorio.';
                    if (empty($this->nc_cognome)) $errors['nc_cognome'] = 'Cognome obbligatorio.';
                } else {
                    if (empty($this->nc_ragione_sociale)) $errors['nc_ragione_sociale'] = 'Ragione sociale obbligatoria.';
                }
            } elseif (! $this->clienteSelezionatoId) {
                $errors['clienteSelezionatoId'] = 'Seleziona o crea un cliente.';
            }

            if (! empty($errors)) {
                foreach ($errors as $field => $msg) {
                    $this->addError($field, $msg);
                }
                return;
            }
        } else {
            $veicolo = Veicolo::find($this->veicoloId);
            if ($veicolo && $this->km_attuali !== null && $this->km_attuali < ($veicolo->km_attuali ?? 0)) {
                $this->addError('km_attuali', 'Km inferiori all\'ultimo valore registrato (' . $veicolo->km_attuali . ' km).');
                return;
            }
        }

        $this->stadio = 3;
    }

    public function tornaAlloStadio(int $n): void
    {
        $this->stadio = max(1, $n);
    }

    // ---------------------------------------------------------------
    // Pacchetti (stadio 3)
    // ---------------------------------------------------------------

    public function updatedCercaPacchetto(): void
    {
        if (strlen($this->cercaPacchetto) < 2 || ! class_exists(PacchettoServizio::class)) {
            $this->suggerimentiPacchetti = [];
            return;
        }

        $this->suggerimentiPacchetti = PacchettoServizio::attivi()
            ->search($this->cercaPacchetto)
            ->with('righe')
            ->orderByDesc('utilizzi')
            ->limit(6)
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'nome'        => $p->nome,
                'totale'      => $p->calcolaTotale(),
                'righe_count' => $p->righe->count(),
            ])
            ->toArray();
    }

    public function selezionaPacchetto(int $id): void
    {
        $pacchetto              = PacchettoServizio::with('righe')->findOrFail($id);
        $this->pacchettoId      = $pacchetto->id;
        $this->cercaPacchetto   = $pacchetto->nome;
        $this->suggerimentiPacchetti = [];

        $this->righePreventivo = $pacchetto->righe->map(fn($r) => [
            'tipo'                  => $r->tipo,
            'descrizione'           => $r->descrizione,
            'articolo_id'           => $r->articolo_id,
            'tariffa_manodopera_id' => null,
            'quantita'              => (float) $r->quantita,
            'prezzo_unitario'       => (float) $r->prezzo_unitario,
            'sconto_percentuale'    => (float) $r->sconto_percentuale,
            'iva_percentuale'       => (float) $r->iva_percentuale,
        ])->toArray();

        $this->calcolaTotalePacchetto();
    }

    public function resetPacchetto(): void
    {
        $this->pacchettoId           = null;
        $this->cercaPacchetto        = '';
        $this->righePreventivo       = [];
        $this->totalePacchetto       = 0;
        $this->suggerimentiPacchetti = [];
    }

    private function calcolaTotalePacchetto(): void
    {
        $this->totalePacchetto = collect($this->righePreventivo)
            ->where('tipo', '!=', 'nota')
            ->sum(fn($r) =>
                (float) ($r['quantita'] ?? 0)
                * (float) ($r['prezzo_unitario'] ?? 0)
                * (1 - (float) ($r['sconto_percentuale'] ?? 0) / 100)
                * (1 + (float) ($r['iva_percentuale'] ?? 22) / 100)
            );
    }

    // ---------------------------------------------------------------
    // Apertura OdL
    // ---------------------------------------------------------------

    public function apriOdl(bool $stampa = false): void
    {
        $this->erroreGenerale = '';

        $errors = [];
        if (empty($this->descrizione_cliente)) $errors['descrizione_cliente'] = 'Campo obbligatorio.';
        if (! in_array($this->tipo, ['meccanica', 'carrozzeria', 'tagliando'])) $errors['tipo'] = 'Tipo non valido.';

        if (! empty($errors)) {
            foreach ($errors as $field => $msg) {
                $this->addError($field, $msg);
            }
            return;
        }

        $dati = [
            'veicolo_id'          => $this->veicoloId,
            'modoNuovoVeicolo'    => $this->modoNuovoVeicolo,
            'targa'               => strtoupper(trim($this->targaInput)),
            'km'                  => $this->modoNuovoVeicolo ? $this->nv_km : $this->km_attuali,
            'nv_tipo'             => $this->nv_tipo,
            'nv_marca'            => $this->nv_marca,
            'nv_modello'          => $this->nv_modello,
            'nv_anno'             => $this->nv_anno,
            'nv_vin'              => $this->nv_vin,
            'nv_alimentazione'    => $this->nv_alimentazione,
            'cliente_id'          => $this->clienteId ?? $this->clienteSelezionatoId,
            'modoNuovoCliente'    => $this->modoNuovoCliente,
            'nc_tipo'             => $this->nc_tipo,
            'nc_nome'             => $this->nc_nome,
            'nc_cognome'          => $this->nc_cognome,
            'nc_ragione_sociale'  => $this->nc_ragione_sociale,
            'nc_telefono'         => $this->nc_telefono,
            'nc_email'            => $this->nc_email,
            'nc_codice_fiscale'   => $this->nc_codice_fiscale,
            'nc_partita_iva'      => $this->nc_partita_iva,
            'tipo'                => $this->tipo,
            'descrizione_cliente' => $this->descrizione_cliente,
            'data_uscita_prevista'=> $this->data_uscita_prevista,
            'pacchetto_id'        => $this->pacchettoId,
            'righe_preventivo'    => $this->righePreventivo,
        ];

        try {
            $commessa = app(CheckInVehicleAction::class)->execute($dati, auth()->user());
        } catch (\Throwable $e) {
            $this->erroreGenerale = 'Errore durante la creazione: ' . $e->getMessage();
            return;
        }

        if ($stampa) {
            $this->dispatch('apriStampaScheda', url: route('pdf.scheda', $commessa->id));
        }

        $this->redirect(route('commesse.show', $commessa->id), navigate: true);
    }

    // ---------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------

    public function render()
    {
        $contextService    = app(AcceptanceContextService::class);
        $ultimiInterventi  = collect();
        $veicoloCorrente   = null;
        $clienteCorrente   = null;

        if ($this->veicoloId && $this->stadio >= 2 && ! $this->modoNuovoVeicolo) {
            $veicoloCorrente  = Veicolo::with('clientePrincipale')->find($this->veicoloId);
            $ultimiInterventi = Commessa::where('veicolo_id', $this->veicoloId)
                ->latest('data_ingresso')
                ->limit(5)
                ->get();

            if ($veicoloCorrente) {
                $clienteCorrente = $veicoloCorrente->clientePrincipale;
            }
        }

        if ($this->stadio >= 2 && $this->modoNuovoVeicolo) {
            $clienteCorrente = $this->clienteSelezionatoId
                ? Cliente::find($this->clienteSelezionatoId)
                : null;
        }

        return view('livewire.acceptance.check-in', [
            'packagesEnabled'   => $contextService->packagesEnabled(),
            'plateLookupEnabled'=> $contextService->plateLookupEnabled(),
            'ultimiInterventi'  => $ultimiInterventi,
            'veicoloCorrente'   => $veicoloCorrente,
            'clienteCorrente'   => $clienteCorrente,
        ]);
    }
}
