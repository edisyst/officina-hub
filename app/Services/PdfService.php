<?php

namespace App\Services;

use App\Models\ChecklistCompilata;
use App\Models\Commessa;
use App\Models\DannoVeicolo;
use App\Models\Documento;
use App\Models\FotoDanno;
use App\Models\PrestitoCortesia;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generaScheda(Commessa $commessa): Response
    {
        $commessa->load(['cliente', 'veicolo', 'righe', 'user']);
        $settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('pdf.scheda', compact('commessa', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("scheda-{$commessa->numero}.pdf");
    }

    public function generaPreventivo(Commessa $commessa): Response
    {
        $commessa->load(['cliente', 'veicolo', 'righe', 'user']);
        $settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('pdf.preventivo', compact('commessa', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("preventivo-{$commessa->numero}.pdf");
    }

    /** PDF di cortesia per una fattura/nota credito (senza valore fiscale) */
    public function fatturaCortesia(Documento $documento): Response
    {
        $documento->load(['cliente', 'righe']);
        $settings = Setting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('pdf.fattura-cortesia', compact('documento', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("cortesia-{$documento->numero}.pdf");
    }

    public function schedaCarrozzeria(Commessa $commessa): Response
    {
        $commessa->load(['cliente', 'veicolo', 'user', 'sinistro.compagniaAssicurativa', 'sinistro.perizia', 'danni']);
        $settings = Setting::pluck('value', 'key')->all();

        // Danni per zona (per il disegno SVG inline nel PDF)
        $danniPerZona = $commessa->danni->groupBy(fn($d) => $d->zona->value)->map->count()->toArray();

        // Foto per fase (miniature — percorsi assoluti per dompdf)
        $fotoPerFase = FotoDanno::where('commessa_id', $commessa->id)
            ->orderBy('fase')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn($f) => $f->fase->value)
            ->map(function ($items) {
                return $items->map(fn($f) => [
                    'path'     => Storage::disk('local')->path($f->percorso),
                    'nome'     => $f->nome_file,
                    'fase'     => $f->fase->label(),
                    'descrizione' => $f->descrizione,
                ])->all();
            })->all();

        $pdf = Pdf::loadView('pdf.scheda-carrozzeria', compact('commessa', 'settings', 'danniPerZona', 'fotoPerFase'))
            ->setPaper('a4', 'portrait');

        $filename = 'carrozzeria-' . str_replace(['/', '\\'], '-', $commessa->numero) . '.pdf';

        return $pdf->download($filename);
    }

    public function contrattoCortesia(PrestitoCortesia $prestito): Response
    {
        $prestito->load(['veicolo', 'cliente', 'commessa', 'utenteConsegna']);
        $settings = Setting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('pdf.contratto-cortesia', compact('prestito', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("contratto-cortesia-{$prestito->id}.pdf");
    }

    public function checklistCompilata(ChecklistCompilata $compilata): Response
    {
        $compilata->load(['template.voci', 'commessa.cliente', 'commessa.veicolo', 'risposte.voce', 'meccanico']);

        $pdf = Pdf::loadView('pdf.checklist', compact('compilata'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("checklist-{$compilata->commessa->numero}-{$compilata->template->nome}.pdf");
    }
}
