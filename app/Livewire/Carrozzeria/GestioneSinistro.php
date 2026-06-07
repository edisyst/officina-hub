<?php

namespace App\Livewire\Carrozzeria;

use App\Enums\StatoSinistro;
use App\Enums\TipoSinistro;
use App\Models\CompagniaAssicurativa;
use App\Models\Commessa;
use App\Models\Perizia;
use App\Models\Sinistro;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class GestioneSinistro extends Component
{
    use WithFileUploads;

    public int $commessaId;
    public ?Sinistro $sinistro = null;
    public ?Perizia $perizia   = null;

    public bool $editSinistro = false;
    public bool $editPerizia  = false;

    // --- Campi sinistro ---
    public ?int $compagnia_assicurativa_id = null;
    public string $numero_sinistro       = '';
    public string $numero_polizza_cliente = '';
    public string $numero_polizza_controparte = '';
    public string $tipo_sinistro         = '';
    public string $data_sinistro         = '';
    public string $luogo_sinistro        = '';
    public string $descrizione_dinamica  = '';
    public string $liquidatore_nome      = '';
    public string $liquidatore_email     = '';
    public string $liquidatore_telefono  = '';
    public string $stato                 = 'aperto';

    // --- Campi perizia ---
    public string $perito_nome                  = '';
    public string $perito_email                 = '';
    public string $data_sopralluogo             = '';
    public string $data_ricezione               = '';
    public string $importo_liquidato            = '';
    public string $importo_franchigia           = '0';
    public string $importo_scoperto_percentuale = '0';
    public string $note_perito                  = '';
    public ?bool  $accettata                    = null;
    public string $motivo_contestazione         = '';

    /** @var mixed File upload perizia PDF */
    public $allegatoPerizia;

    public function mount(int $commessaId): void
    {
        $this->commessaId = $commessaId;
        $this->carica();
    }

    private function carica(): void
    {
        $commessa = Commessa::with(['sinistro.compagniaAssicurativa', 'sinistro.perizia'])->find($this->commessaId);
        $this->sinistro = $commessa?->sinistro;
        $this->perizia  = $this->sinistro?->perizia;
    }

    public function apriFormSinistro(): void
    {
        if ($this->sinistro) {
            $s = $this->sinistro;
            $this->compagnia_assicurativa_id    = $s->compagnia_assicurativa_id;
            $this->numero_sinistro              = $s->numero_sinistro ?? '';
            $this->numero_polizza_cliente       = $s->numero_polizza_cliente ?? '';
            $this->numero_polizza_controparte   = $s->numero_polizza_controparte ?? '';
            $this->tipo_sinistro                = $s->tipo_sinistro->value ?? '';
            $this->data_sinistro                = $s->data_sinistro?->format('Y-m-d') ?? '';
            $this->luogo_sinistro               = $s->luogo_sinistro ?? '';
            $this->descrizione_dinamica         = $s->descrizione_dinamica ?? '';
            $this->liquidatore_nome             = $s->liquidatore_nome ?? '';
            $this->liquidatore_email            = $s->liquidatore_email ?? '';
            $this->liquidatore_telefono         = $s->liquidatore_telefono ?? '';
            $this->stato                        = $s->stato->value;
        } else {
            $this->reset(['compagnia_assicurativa_id', 'numero_sinistro', 'numero_polizza_cliente',
                'numero_polizza_controparte', 'tipo_sinistro', 'data_sinistro', 'luogo_sinistro',
                'descrizione_dinamica', 'liquidatore_nome', 'liquidatore_email', 'liquidatore_telefono']);
            $this->stato = 'aperto';
        }
        $this->editSinistro = true;
    }

    public function salvaSinistro(): void
    {
        $this->validate([
            'tipo_sinistro'    => 'required|in:' . implode(',', array_column(TipoSinistro::cases(), 'value')),
            'stato'            => 'required|in:' . implode(',', array_column(StatoSinistro::cases(), 'value')),
            'data_sinistro'    => 'nullable|date',
            'liquidatore_email'=> 'nullable|email',
        ]);

        $data = [
            'commessa_id'                 => $this->commessaId,
            'compagnia_assicurativa_id'   => $this->compagnia_assicurativa_id ?: null,
            'numero_sinistro'             => $this->numero_sinistro ?: null,
            'numero_polizza_cliente'      => $this->numero_polizza_cliente ?: null,
            'numero_polizza_controparte'  => $this->numero_polizza_controparte ?: null,
            'tipo_sinistro'               => $this->tipo_sinistro,
            'data_sinistro'               => $this->data_sinistro ?: null,
            'luogo_sinistro'              => $this->luogo_sinistro ?: null,
            'descrizione_dinamica'        => $this->descrizione_dinamica ?: null,
            'liquidatore_nome'            => $this->liquidatore_nome ?: null,
            'liquidatore_email'           => $this->liquidatore_email ?: null,
            'liquidatore_telefono'        => $this->liquidatore_telefono ?: null,
            'stato'                       => $this->stato,
        ];

        if ($this->sinistro) {
            $this->sinistro->update($data);
        } else {
            $sinistro = Sinistro::create($data);
            // Collega il sinistro alla commessa
            Commessa::where('id', $this->commessaId)->update(['sinistro_id' => $sinistro->id]);
        }

        $this->editSinistro = false;
        $this->carica();
        session()->flash('success', 'Sinistro salvato.');
    }

    public function apriFormPerizia(): void
    {
        if ($this->perizia) {
            $p = $this->perizia;
            $this->perito_nome                  = $p->perito_nome ?? '';
            $this->perito_email                 = $p->perito_email ?? '';
            $this->data_sopralluogo             = $p->data_sopralluogo?->format('Y-m-d') ?? '';
            $this->data_ricezione               = $p->data_ricezione?->format('Y-m-d') ?? '';
            $this->importo_liquidato            = $p->importo_liquidato ?? '';
            $this->importo_franchigia           = $p->importo_franchigia ?? '0';
            $this->importo_scoperto_percentuale = $p->importo_scoperto_percentuale ?? '0';
            $this->note_perito                  = $p->note_perito ?? '';
            $this->accettata                    = $p->accettata;
            $this->motivo_contestazione         = $p->motivo_contestazione ?? '';
        } else {
            $this->reset(['perito_nome', 'perito_email', 'data_sopralluogo', 'data_ricezione',
                'importo_liquidato', 'note_perito', 'accettata', 'motivo_contestazione']);
            $this->importo_franchigia           = '0';
            $this->importo_scoperto_percentuale = '0';
        }
        $this->editPerizia = true;
    }

    public function salvaPerizia(): void
    {
        $this->validate([
            'perito_email'                  => 'nullable|email',
            'data_sopralluogo'              => 'nullable|date',
            'data_ricezione'                => 'nullable|date',
            'importo_liquidato'             => 'nullable|numeric|min:0',
            'importo_franchigia'            => 'nullable|numeric|min:0',
            'importo_scoperto_percentuale'  => 'nullable|numeric|min:0|max:100',
            'allegatoPerizia' => [
                'nullable', 'file',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:20480',
            ],
        ]);

        $perizia = $this->perizia ?? new Perizia(['sinistro_id' => $this->sinistro->id]);
        $perizia->perito_nome                  = $this->perito_nome ?: null;
        $perizia->perito_email                 = $this->perito_email ?: null;
        $perizia->data_sopralluogo             = $this->data_sopralluogo ?: null;
        $perizia->data_ricezione               = $this->data_ricezione ?: null;
        $perizia->importo_liquidato            = $this->importo_liquidato !== '' ? $this->importo_liquidato : null;
        $perizia->importo_franchigia           = $this->importo_franchigia ?: 0;
        $perizia->importo_scoperto_percentuale = $this->importo_scoperto_percentuale ?: 0;
        $perizia->note_perito                  = $this->note_perito ?: null;
        $perizia->accettata                    = $this->accettata;
        $perizia->motivo_contestazione         = $this->motivo_contestazione ?: null;
        $perizia->calcolaNetto();

        if ($this->allegatoPerizia) {
            // Rimuove il vecchio file se esiste
            if ($perizia->allegato_perizia_path) {
                Storage::disk('local')->delete($perizia->allegato_perizia_path);
            }
            $filename = 'allegati/perizie/perizia_' . Str::random(16) . '.pdf';
            $this->allegatoPerizia->storeAs('', $filename, 'local');
            $perizia->allegato_perizia_path = $filename;
        }

        $perizia->save();

        $this->allegatoPerizia = null;
        $this->editPerizia     = false;
        $this->carica();
        session()->flash('success', 'Perizia salvata.');
    }

    public function render()
    {
        $compagnie = CompagniaAssicurativa::orderBy('nome')->get(['id', 'nome']);
        $tipiSinistro  = TipoSinistro::cases();
        $statiSinistro = StatoSinistro::cases();

        return view('livewire.carrozzeria.gestione-sinistro', compact('compagnie', 'tipiSinistro', 'statiSinistro'));
    }
}
