<?php

namespace App\Livewire\Impostazioni;

use App\Models\TariffaManodopera;
use Illuminate\Http\Response;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TariffeManodopera extends Component
{
    use WithPagination, WithFileUploads;

    public string $cerca = '';
    public string $filtroCategoria = '';
    public string $filtroTipoVeicolo = '';

    public bool $showModal = false;
    public ?int $tariffaId = null;

    #[Rule('required|string|max:20|unique:tariffe_manodopera,codice')]
    public string $codice = '';

    #[Rule('required|string|max:255')]
    public string $descrizione = '';

    #[Rule('required|string|max:100')]
    public string $categoria = '';

    #[Rule('required|integer|min:1')]
    public int $minuti_standard = 60;

    #[Rule('required|numeric|min:0')]
    public float $prezzo_listino = 0;

    #[Rule('required|numeric|min:0|max:100')]
    public float $iva_percentuale = 22;

    #[Rule('required|in:auto,moto,entrambi')]
    public string $tipo_veicolo = 'entrambi';

    public ?string $note = null;

    // CSV import
    #[Rule('nullable|file|mimes:csv,txt|max:2048')]
    public $csvFile = null;

    public ?string $importMessaggio = null;
    public bool $importErrore = false;

    public function updatedCerca(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroCategoria(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroTipoVeicolo(): void
    {
        $this->resetPage();
    }

    public function apriNuovo(): void
    {
        $this->reset(['tariffaId', 'codice', 'descrizione', 'categoria', 'minuti_standard', 'prezzo_listino', 'note']);
        $this->iva_percentuale = 22;
        $this->tipo_veicolo = 'entrambi';
        $this->minuti_standard = 60;
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $tariffa = TariffaManodopera::findOrFail($id);
        $this->tariffaId         = $tariffa->id;
        $this->codice            = $tariffa->codice;
        $this->descrizione       = $tariffa->descrizione;
        $this->categoria         = $tariffa->categoria;
        $this->minuti_standard   = $tariffa->minuti_standard;
        $this->prezzo_listino    = (float) $tariffa->prezzo_listino;
        $this->iva_percentuale   = (float) $tariffa->iva_percentuale;
        $this->tipo_veicolo      = $tariffa->tipo_veicolo;
        $this->note              = $tariffa->note;
        $this->showModal         = true;
    }

    public function salva(): void
    {
        $rules = $this->getRules();

        if ($this->tariffaId) {
            $rules['codice'] = 'required|string|max:20|unique:tariffe_manodopera,codice,' . $this->tariffaId;
        }

        $this->validate($rules);

        $dati = [
            'codice'          => strtoupper($this->codice),
            'descrizione'     => $this->descrizione,
            'categoria'       => $this->categoria,
            'minuti_standard' => $this->minuti_standard,
            'prezzo_listino'  => $this->prezzo_listino,
            'iva_percentuale' => $this->iva_percentuale,
            'tipo_veicolo'    => $this->tipo_veicolo,
            'note'            => $this->note ?: null,
        ];

        if ($this->tariffaId) {
            TariffaManodopera::findOrFail($this->tariffaId)->update($dati);
            session()->flash('success', 'Tariffa aggiornata.');
        } else {
            TariffaManodopera::create(array_merge($dati, ['attivo' => true]));
            session()->flash('success', 'Tariffa creata.');
        }

        $this->showModal = false;
    }

    public function toggleAttivo(int $id): void
    {
        $tariffa = TariffaManodopera::findOrFail($id);
        $tariffa->update(['attivo' => ! $tariffa->attivo]);
    }

    public function elimina(int $id): void
    {
        TariffaManodopera::findOrFail($id)->delete();
        session()->flash('success', 'Tariffa eliminata.');
    }

    public function importaCsv(): void
    {
        $this->validateOnly('csvFile');

        if (! $this->csvFile) {
            return;
        }

        $path    = $this->csvFile->getRealPath();
        $handle  = fopen($path, 'r');
        $header  = fgetcsv($handle, 0, ';');

        // Normalizza header (rimuovi BOM UTF-8 se presente)
        if ($header && str_starts_with($header[0], "\xEF\xBB\xBF")) {
            $header[0] = substr($header[0], 3);
        }

        $righeImportate  = 0;
        $righeErrore     = 0;
        $riga            = 2;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 6) {
                $righeErrore++;
                $riga++;
                continue;
            }

            [$codice, $descrizione, $categoria, $minuti, $prezzo, $iva, $tipoVeicolo] = array_pad($row, 7, 'entrambi');

            $codice      = trim(strtoupper($codice));
            $descrizione = trim($descrizione);
            $categoria   = trim($categoria);
            $minuti      = (int) trim($minuti);
            $prezzo      = (float) str_replace(',', '.', trim($prezzo));
            $iva         = (float) str_replace(',', '.', trim($iva));
            $tipoVeicolo = in_array(trim($tipoVeicolo), ['auto', 'moto', 'entrambi']) ? trim($tipoVeicolo) : 'entrambi';

            if (empty($codice) || empty($descrizione) || empty($categoria) || $minuti < 1) {
                $righeErrore++;
                $riga++;
                continue;
            }

            TariffaManodopera::updateOrCreate(
                ['codice' => $codice],
                [
                    'descrizione'     => $descrizione,
                    'categoria'       => $categoria,
                    'minuti_standard' => $minuti,
                    'prezzo_listino'  => $prezzo,
                    'iva_percentuale' => $iva ?: 22,
                    'tipo_veicolo'    => $tipoVeicolo,
                    'attivo'          => true,
                ]
            );

            $righeImportate++;
            $riga++;
        }

        fclose($handle);

        $this->csvFile      = null;
        $this->importErrore = $righeErrore > 0;
        $this->importMessaggio = "Importate: {$righeImportate} tariffe. Saltate: {$righeErrore} righe con errori.";
    }

    public function scaricaTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF" . implode(';', ['codice', 'descrizione', 'categoria', 'minuti_standard', 'prezzo_listino', 'iva_percentuale', 'tipo_veicolo']) . "\n";
        $csv .= implode(';', ['MOD-999', 'Esempio lavorazione', 'Motore', '60', '50.00', '22', 'entrambi']) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_tariffe.csv"',
        ]);
    }

    public function esportaCsv(): Response
    {
        $tariffe = TariffaManodopera::withTrashed()->orderBy('categoria')->orderBy('codice')->get();

        $righe = ["\xEF\xBB\xBF" . implode(';', ['codice', 'descrizione', 'categoria', 'minuti_standard', 'prezzo_listino', 'iva_percentuale', 'tipo_veicolo', 'attivo'])];

        foreach ($tariffe as $t) {
            $righe[] = implode(';', [
                $t->codice,
                '"' . str_replace('"', '""', $t->descrizione) . '"',
                '"' . str_replace('"', '""', $t->categoria) . '"',
                $t->minuti_standard,
                number_format((float) $t->prezzo_listino, 2, ',', ''),
                number_format((float) $t->iva_percentuale, 2, ',', ''),
                $t->tipo_veicolo,
                $t->attivo ? '1' : '0',
            ]);
        }

        return response(implode("\n", $righe), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tariffe_manodopera_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    public function render()
    {
        $tariffe = TariffaManodopera::when($this->cerca, fn($q) => $q->search($this->cerca))
            ->when($this->filtroCategoria, fn($q) => $q->where('categoria', $this->filtroCategoria))
            ->when($this->filtroTipoVeicolo, fn($q) => $q->where('tipo_veicolo', $this->filtroTipoVeicolo))
            ->orderBy('categoria')
            ->orderBy('codice')
            ->paginate(20);

        $categorie = TariffaManodopera::whereNull('deleted_at')->distinct()->orderBy('categoria')->pluck('categoria');

        return view('livewire.impostazioni.tariffe-manodopera', compact('tariffe', 'categorie'));
    }
}
