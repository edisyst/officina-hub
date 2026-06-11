<?php

namespace App\Livewire\Clienti;

use App\Enums\SegmentoCrm;
use App\Enums\TipoCliente;
use App\Models\Cliente;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ListaClienti extends Component
{
    use WithPagination;

    public string $search            = '';
    public string $filtroSegmento    = '';
    public string $filtroConsenso    = '';
    public string $filtroValoreMin   = '';
    public string $filtroValoreMax   = '';
    public string $filtroUltimaVisitaDa = '';
    public string $filtroUltimaVisitaA  = '';
    public bool $showModal = false;
    public bool $showTrashedModal = false;
    public ?int $editingId = null;

    #[Rule('required|in:fisica,giuridica')]
    public string $tipo = 'fisica';

    #[Rule('nullable|string|max:100')]
    public ?string $nome = null;

    #[Rule('nullable|string|max:100')]
    public ?string $cognome = null;

    #[Rule('nullable|string|max:200')]
    public ?string $ragione_sociale = null;

    #[Rule('nullable|string|max:16')]
    public ?string $codice_fiscale = null;

    #[Rule('nullable|string|max:11')]
    public ?string $partita_iva = null;

    #[Rule('nullable|email|max:255')]
    public ?string $email = null;

    #[Rule('nullable|string|max:30')]
    public ?string $telefono = null;

    #[Rule('nullable|string|max:255')]
    public ?string $indirizzo = null;

    #[Rule('nullable|string|max:100')]
    public ?string $citta = null;

    #[Rule('nullable|string|max:10')]
    public ?string $cap = null;

    #[Rule('nullable|string|max:5')]
    public ?string $provincia = null;

    #[Rule('nullable|string')]
    public ?string $note = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltroSegmento(): void { $this->resetPage(); }
    public function updatingFiltroConsenso(): void { $this->resetPage(); }

    public function resetFiltriCrm(): void
    {
        $this->filtroSegmento        = '';
        $this->filtroConsenso        = '';
        $this->filtroValoreMin       = '';
        $this->filtroValoreMax       = '';
        $this->filtroUltimaVisitaDa  = '';
        $this->filtroUltimaVisitaA   = '';
        $this->resetPage();
    }

    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $clienti = $this->buildQuery()->get();

        return response()->streamDownload(function () use ($clienti) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 per Excel italiano
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID', 'Tipo', 'Nome', 'Cognome', 'Ragione Sociale',
                'Email', 'Telefono', 'Città', 'Segmento CRM',
                'Valore Lifetime', 'N. Visite', 'Ultima Visita',
                'Consenso Marketing',
            ], ';');

            foreach ($clienti as $c) {
                fputcsv($handle, [
                    $c->id,
                    $c->tipo?->value ?? '',
                    $c->nome ?? '',
                    $c->cognome ?? '',
                    $c->ragione_sociale ?? '',
                    $c->email ?? '',
                    $c->telefono ?? '',
                    $c->citta ?? '',
                    $c->segmento_crm?->value ?? '',
                    number_format((float) $c->valore_lifetime, 2, ',', '.'),
                    $c->numero_visite,
                    $c->ultima_visita_at?->format('d/m/Y') ?? '',
                    $c->consenso_marketing ? 'Sì' : 'No',
                ], ';');
            }

            fclose($handle);
        }, 'clienti_crm_' . now()->format('Ymd') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildQuery()
    {
        return Cliente::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filtroSegmento, fn($q) => $q->where('segmento_crm', $this->filtroSegmento))
            ->when($this->filtroConsenso !== '', fn($q) => $q->where('consenso_marketing', (bool) $this->filtroConsenso))
            ->when($this->filtroValoreMin, fn($q) => $q->where('valore_lifetime', '>=', (float) $this->filtroValoreMin))
            ->when($this->filtroValoreMax, fn($q) => $q->where('valore_lifetime', '<=', (float) $this->filtroValoreMax))
            ->when($this->filtroUltimaVisitaDa, fn($q) => $q->where('ultima_visita_at', '>=', $this->filtroUltimaVisitaDa))
            ->when($this->filtroUltimaVisitaA, fn($q) => $q->where('ultima_visita_at', '<=', $this->filtroUltimaVisitaA));
    }

    public function apriModal(?int $id = null): void
    {
        $this->reset(['tipo', 'nome', 'cognome', 'ragione_sociale', 'codice_fiscale',
            'partita_iva', 'email', 'telefono', 'indirizzo', 'citta', 'cap', 'provincia', 'note']);
        $this->editingId = $id;

        if ($id) {
            $cliente = Cliente::findOrFail($id);
            $this->fill([
                'tipo' => $cliente->tipo->value,
                'nome' => $cliente->nome,
                'cognome' => $cliente->cognome,
                'ragione_sociale' => $cliente->ragione_sociale,
                'codice_fiscale' => $cliente->codice_fiscale,
                'partita_iva' => $cliente->partita_iva,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'indirizzo' => $cliente->indirizzo,
                'citta' => $cliente->citta,
                'cap' => $cliente->cap,
                'provincia' => $cliente->provincia,
                'note' => $cliente->note,
            ]);
        }

        $this->showModal = true;
    }

    public function chiudiModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function salva(): void
    {
        $this->validate();

        $dati = [
            'tipo' => $this->tipo,
            'nome' => $this->nome,
            'cognome' => $this->cognome,
            'ragione_sociale' => $this->ragione_sociale,
            'codice_fiscale' => $this->codice_fiscale ?: null,
            'partita_iva' => $this->partita_iva ?: null,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'indirizzo' => $this->indirizzo,
            'citta' => $this->citta,
            'cap' => $this->cap,
            'provincia' => $this->provincia,
            'note' => $this->note,
        ];

        if ($this->editingId) {
            $cliente = Cliente::findOrFail($this->editingId);
            $this->authorize('update', $cliente);
            $cliente->update($dati);
            session()->flash('success', 'Cliente aggiornato con successo.');
        } else {
            $this->authorize('create', Cliente::class);
            Cliente::create($dati);
            session()->flash('success', 'Cliente creato con successo.');
        }

        $this->chiudiModal();
    }

    public function elimina(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->authorize('delete', $cliente);
        $cliente->delete();
        session()->flash('success', 'Cliente eliminato.');
    }

    public function ripristina(int $id): void
    {
        $cliente = Cliente::withTrashed()->findOrFail($id);
        $this->authorize('restore', $cliente);
        $cliente->restore();
        session()->flash('success', 'Cliente ripristinato.');
    }

    public function render()
    {
        $clienti = $this->buildQuery()
            ->orderBy('cognome')
            ->orderBy('ragione_sociale')
            ->paginate(20);

        $eliminati = Cliente::onlyTrashed()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->get();

        return view('livewire.clienti.lista-clienti', [
            'clienti'       => $clienti,
            'eliminati'     => $eliminati,
            'tipiCliente'   => TipoCliente::cases(),
            'segmentiCrm'   => SegmentoCrm::cases(),
        ]);
    }
}
