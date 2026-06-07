<?php

namespace App\Http\Controllers;

use App\Enums\StatoApprovazioneDvi;
use App\Enums\StatoDviIspezione;
use App\Models\DviIspezione;
use App\Models\DviMedia;
use App\Jobs\NotificaDviRisposta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DviController extends Controller
{
    /** Portale cliente pubblico */
    public function portaleCliente(string $token)
    {
        $ispezione = DviIspezione::where('link_token', $token)
            ->with(['commessa.cliente', 'commessa.veicolo', 'voci.media'])
            ->first();

        if (! $ispezione) {
            return view('dvi.portale-cliente', ['errore' => 'non_trovato']);
        }

        if ($ispezione->stato === StatoDviIspezione::InviataCliente
            && $ispezione->link_scade_at->isPast()) {
            return view('dvi.portale-cliente', ['errore' => 'scaduto', 'ispezione' => $ispezione]);
        }

        if (! in_array($ispezione->stato, [
            StatoDviIspezione::InviataCliente,
            StatoDviIspezione::Approvata,
            StatoDviIspezione::ParzialmenteApprovata,
            StatoDviIspezione::Rifiutata,
        ])) {
            return view('dvi.portale-cliente', ['errore' => 'non_trovato']);
        }

        $giaRisposto = $ispezione->stato !== StatoDviIspezione::InviataCliente;

        return view('dvi.portale-cliente', compact('ispezione', 'token', 'giaRisposto'));
    }

    /** Salva le risposte del cliente */
    public function salvaRisposte(Request $request, string $token)
    {
        $ispezione = DviIspezione::where('link_token', $token)
            ->with('voci')
            ->firstOrFail();

        if ($ispezione->stato !== StatoDviIspezione::InviataCliente) {
            return redirect()->route('dvi.portale', $token);
        }

        if ($ispezione->link_scade_at->isPast()) {
            return redirect()->route('dvi.portale', $token);
        }

        $request->validate([
            'risposte'     => 'required|array',
            'risposte.*'   => 'required|in:approvato,rimandato',
            'note_cliente' => 'nullable|string|max:1000',
        ]);

        $risposte = $request->input('risposte', []);
        $now = now();

        foreach ($ispezione->voci as $voce) {
            if (isset($risposte[$voce->id])) {
                $stato = $risposte[$voce->id] === 'approvato'
                    ? StatoApprovazioneDvi::Approvato
                    : StatoApprovazioneDvi::Rimandato;

                $voce->update([
                    'stato_approvazione' => $stato,
                    'approvato_at'       => $now,
                ]);
            }
        }

        $ispezione->refresh();
        $voci = $ispezione->voci;

        $tutteApprovate  = $voci->every(fn($v) => $v->stato_approvazione === StatoApprovazioneDvi::Approvato);
        $tutteRifiutate  = $voci->every(fn($v) => $v->stato_approvazione === StatoApprovazioneDvi::Rimandato);
        $almeno1Approvata = $voci->some(fn($v) => $v->stato_approvazione === StatoApprovazioneDvi::Approvato);

        $nuovoStato = match(true) {
            $tutteApprovate   => StatoDviIspezione::Approvata,
            $tutteRifiutate   => StatoDviIspezione::Rifiutata,
            $almeno1Approvata => StatoDviIspezione::ParzialmenteApprovata,
            default           => StatoDviIspezione::Rifiutata,
        };

        $importo = $voci
            ->filter(fn($v) => $v->stato_approvazione?->value === StatoApprovazioneDvi::Approvato->value)
            ->sum('prezzo_stimato');

        $ispezione->update([
            'stato'        => $nuovoStato,
            'approvata_at' => $now,
            'note_cliente' => $request->input('note_cliente'),
        ]);

        $ispezione->commessa->update(['dvi_approvazione_importo' => $importo]);

        NotificaDviRisposta::dispatch($ispezione);

        return redirect()->route('dvi.conferma', $token);
    }

    /** Pagina di conferma post-risposta */
    public function conferma(string $token)
    {
        $ispezione = DviIspezione::where('link_token', $token)
            ->with(['commessa.cliente', 'commessa.veicolo', 'voci'])
            ->firstOrFail();

        return view('dvi.conferma', compact('ispezione'));
    }

    /** Serve media (foto/video) per staff autenticato */
    public function serveMedia(DviMedia $media)
    {
        abort_unless(Storage::disk('local')->exists($media->percorso), 404);
        return response()->file(
            Storage::disk('local')->path($media->percorso),
            ['Content-Type' => $media->mime_type]
        );
    }

    /** Serve thumbnail per staff autenticato */
    public function serveThumbnail(DviMedia $media)
    {
        if ($media->thumbnail_path && Storage::disk('local')->exists($media->thumbnail_path)) {
            return response()->file(Storage::disk('local')->path($media->thumbnail_path));
        }
        // Placeholder SVG con icona play
        return response(
            '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="90" viewBox="0 0 160 90">'
            . '<rect width="160" height="90" fill="#1a1a2e"/>'
            . '<polygon points="60,25 60,65 110,45" fill="white" opacity="0.8"/>'
            . '</svg>',
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }

    /** Serve media per il cliente (token-based) */
    public function serveMediaCliente(string $token, DviMedia $media)
    {
        $ispezione = DviIspezione::where('link_token', $token)->firstOrFail();

        abort_unless($media->dvi_ispezione_id === $ispezione->id, 403);
        abort_unless(Storage::disk('local')->exists($media->percorso), 404);

        return response()->file(
            Storage::disk('local')->path($media->percorso),
            ['Content-Type' => $media->mime_type]
        );
    }

    /** Anteprima DVI per lo staff */
    public function anteprima(DviIspezione $ispezione)
    {
        $ispezione->load(['commessa.cliente', 'commessa.veicolo', 'voci.media']);
        return view('dvi.anteprima', compact('ispezione'));
    }
}
