<?php

namespace App\Http\Controllers;

use App\Models\PrestitoCortesia;
use App\Services\PdfService;

class CortesiaController extends Controller
{
    public function contrattoPdf(PrestitoCortesia $prestito, PdfService $pdf)
    {
        $this->authorize('viewAny', $prestito);
        return $pdf->contrattoCortesia($prestito);
    }
}
