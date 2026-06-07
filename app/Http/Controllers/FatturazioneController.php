<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Services\FatturaPAService;
use App\Services\PdfService;
use Symfony\Component\HttpFoundation\Response;

class FatturazioneController extends Controller
{
    public function __construct(
        private readonly FatturaPAService $fatturaPAService,
        private readonly PdfService $pdfService,
    ) {}

    /** Scarica l'XML FatturaPA già generato e salvato sul documento */
    public function scaricaXml(Documento $documento): Response
    {
        $this->authorize('generaXml', $documento);

        if (empty($documento->xml_generato)) {
            abort(404, 'XML non ancora generato. Usare il pulsante "Genera XML" nel dettaglio documento.');
        }

        return response($documento->xml_generato, 200, [
            'Content-Type'        => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $documento->nome_file_sdi . '"',
        ]);
    }

    /** Scarica il pacchetto ZIP SdI contenente l'XML */
    public function scaricaZip(Documento $documento): Response
    {
        $this->authorize('generaXml', $documento);

        if (empty($documento->xml_generato)) {
            abort(404, 'XML non ancora generato.');
        }

        $zipPath = $this->fatturaPAService->pacchetto($documento);

        return response()->download($zipPath, pathinfo($zipPath, PATHINFO_BASENAME), [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(false);
    }

    /** Scarica il PDF di cortesia (senza valore fiscale) */
    public function scaricaPdf(Documento $documento): Response
    {
        $this->authorize('view', $documento);

        return $this->pdfService->fatturaCortesia($documento);
    }
}
