<?php

namespace App\Http\Controllers;

use App\Models\Commessa;
use App\Models\FotoDanno;
use App\Services\PdfService;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CarrozzeriaController extends Controller
{
    public function __construct(private PdfService $pdfService) {}

    /** Scarica tutte le foto danni della commessa in un archivio ZIP */
    public function downloadZipFoto(Commessa $commessa)
    {
        $this->authorize('view', $commessa);

        $foto = FotoDanno::where('commessa_id', $commessa->id)->get();

        if ($foto->isEmpty()) {
            return back()->with('error', 'Nessuna foto disponibile per il download.');
        }

        $filename = "foto-danni-{$commessa->numero}.zip";

        return response()->streamDownload(function () use ($foto) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'officina-zip');

            $zip = new ZipArchive();
            if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($foto as $f) {
                    $path = Storage::disk('local')->path($f->percorso);
                    if (file_exists($path)) {
                        $zip->addFile($path, $f->fase->value . '/' . $f->nome_file);
                    }
                }
                $zip->close();
            }

            readfile($tmpFile);
            @unlink($tmpFile);
        }, $filename, ['Content-Type' => 'application/zip']);
    }

    /** PDF scheda carrozzeria */
    public function schedaCarrozzeria(Commessa $commessa)
    {
        $this->authorize('view', $commessa);
        return $this->pdfService->schedaCarrozzeria($commessa);
    }
}
