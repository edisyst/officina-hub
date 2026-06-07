<?php

namespace App\Http\Controllers;

use App\Models\Commessa;
use App\Services\PdfService;

class PdfController extends Controller
{
    public function __construct(private PdfService $pdfService) {}

    public function scheda(Commessa $commessa)
    {
        $this->authorize('view', $commessa);
        return $this->pdfService->generaScheda($commessa);
    }

    public function preventivo(Commessa $commessa)
    {
        $this->authorize('view', $commessa);
        return $this->pdfService->generaPreventivo($commessa);
    }
}
