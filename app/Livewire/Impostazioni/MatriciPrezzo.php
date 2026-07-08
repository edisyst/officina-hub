<?php

namespace App\Livewire\Impostazioni;

use App\Models\MatricePrezzo;
use App\Models\MatricePrezzoScaglione;
use App\Services\Pricing\MatricePrezzoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class MatriciPrezzo extends Component
{
    use WithPagination;

    public bool $showModal  = false;
    public ?int $matriceId  = null;

    public string $nome     = '';
    public bool $is_attiva  = true;

    // Scaglioni in editing: array di ['costo_da','costo_a','markup_percent','arrotondamento']
    public array $scaglioni = [];

    // Anteprima live
    public string $anteprimaCosto    = '';
    public string $anteprimaRisultato = '';

    protected MatricePrezzoService $service;

    public function boot(MatricePrezzoService $service): void
    {
        $this->service = $service;
    }

    public function apriNuovo(): void
    {
        $this->reset(['matriceId', 'nome', 'anteprimaCosto', 'anteprimaRisultato']);
        $this->is_attiva  = true;
        $this->scaglioni  = [
            ['costo_da' => '0.00', 'costo_a' => '', 'markup_percent' => '50.00', 'arrotondamento' => 'none'],
        ];
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $matrice = MatricePrezzo::with('scaglioni')->findOrFail($id);
        $this->matriceId  = $matrice->id;
        $this->nome       = $matrice->nome;
        $this->is_attiva  = $matrice->is_attiva;
        $this->scaglioni  = $matrice->scaglioni->map(fn($s) => [
            'costo_da'      => (string) $s->costo_da,
            'costo_a'       => $s->costo_a !== null ? (string) $s->costo_a : '',
            'markup_percent' => (string) $s->markup_percent,
            'arrotondamento' => $s->arrotondamento,
        ])->toArray();
        $this->reset(['anteprimaCosto', 'anteprimaRisultato']);
        $this->showModal = true;
    }

    public function aggiungiScaglione(): void
    {
        // costo_da = costo_a precedente o 0
        $last   = end($this->scaglioni);
        $costoA = $last ? (string) ($last['costo_a'] ?? '') : '0.00';
        // Se l'ultimo aveva costo_a vuoto, apriamo dal 0
        if ($costoA === '') {
            $costoA = '0.00';
        }
        // Sposta costo_a del precedente al valore corrente se era vuoto
        if (! empty($this->scaglioni)) {
            $lastKey = array_key_last($this->scaglioni);
            if ($this->scaglioni[$lastKey]['costo_a'] === '') {
                $this->scaglioni[$lastKey]['costo_a'] = $costoA;
            }
        }
        $this->scaglioni[] = [
            'costo_da'      => $costoA,
            'costo_a'       => '',
            'markup_percent' => '50.00',
            'arrotondamento' => 'none',
        ];
    }

    public function rimuoviScaglione(int $index): void
    {
        array_splice($this->scaglioni, $index, 1);
    }

    public function updatedAnteprimaCosto(): void
    {
        $this->calcolaAnteprima();
    }

    public function updatedScaglioni(): void
    {
        $this->calcolaAnteprima();
    }

    private function calcolaAnteprima(): void
    {
        $costo = (float) str_replace(',', '.', $this->anteprimaCosto);
        if ($costo <= 0) {
            $this->anteprimaRisultato = '';
            return;
        }

        // Costruiamo una matrice temporanea in memoria
        $fakeMatrix = new MatricePrezzo(['nome' => 'preview', 'is_default' => false, 'is_attiva' => true]);
        $fakeMatrix->setRelation('scaglioni', collect($this->scaglioni)->map(fn($s) => new MatricePrezzoScaglione([
            'costo_da'      => (float) str_replace(',', '.', $s['costo_da'] ?? 0),
            'costo_a'       => ($s['costo_a'] ?? '') !== '' ? (float) str_replace(',', '.', $s['costo_a']) : null,
            'markup_percent' => (float) str_replace(',', '.', $s['markup_percent'] ?? 0),
            'arrotondamento' => $s['arrotondamento'] ?? 'none',
        ])));

        try {
            $result = $this->service->suggestPrice($costo, $fakeMatrix);
            $this->anteprimaRisultato = $result !== null
                ? '€ ' . number_format((float) $result, 2, ',', '.')
                : '—';
        } catch (\Throwable) {
            $this->anteprimaRisultato = '—';
        }
    }

    public function salva(): void
    {
        $this->validate([
            'nome'                          => 'required|string|max:255',
            'scaglioni'                     => 'required|array|min:1',
            'scaglioni.*.costo_da'          => 'required|numeric|min:0',
            'scaglioni.*.costo_a'           => 'nullable|numeric',
            'scaglioni.*.markup_percent'    => 'required|numeric|min:0',
            'scaglioni.*.arrotondamento'    => 'required|in:none,0.10,0.50,1.00',
        ]);

        // Normalizza costo_a null
        $scaglioni = collect($this->scaglioni)->map(fn($s) => array_merge($s, [
            'costo_a' => ($s['costo_a'] ?? '') !== '' ? (float) $s['costo_a'] : null,
        ]))->toArray();

        try {
            $this->service->validateScaglioni($scaglioni);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $msgs) {
                $this->addError($field, $msgs[0]);
            }
            return;
        }

        DB::transaction(function () use ($scaglioni) {
            $matrice = $this->matriceId
                ? MatricePrezzo::findOrFail($this->matriceId)
                : new MatricePrezzo();

            $matrice->fill(['nome' => $this->nome, 'is_attiva' => $this->is_attiva]);
            $matrice->save();

            $matrice->scaglioni()->delete();
            foreach ($scaglioni as $s) {
                $matrice->scaglioni()->create($s);
            }
        });

        session()->flash('success', $this->matriceId ? 'Matrice aggiornata.' : 'Matrice creata.');
        $this->showModal = false;
    }

    public function impostaDefault(int $id): void
    {
        $this->service->setDefault(MatricePrezzo::findOrFail($id));
        session()->flash('success', 'Matrice impostata come default.');
    }

    public function toggleAttiva(int $id): void
    {
        $matrice = MatricePrezzo::findOrFail($id);
        if ($matrice->is_default && $matrice->is_attiva) {
            session()->flash('error', 'Non puoi disattivare la matrice default. Imposta prima un\'altra come default.');
            return;
        }
        $matrice->update(['is_attiva' => ! $matrice->is_attiva]);
    }

    public function render()
    {
        $matrici = MatricePrezzo::withCount('scaglioni')->orderByDesc('is_default')->orderBy('nome')->paginate(10);

        return view('livewire.impostazioni.matrici-prezzo', compact('matrici'));
    }
}
