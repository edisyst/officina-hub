<?php

namespace App\Http\Controllers;

use App\Models\Commessa;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function commessa(Commessa $commessa): Response
    {
        $this->authorize('view', $commessa);

        $url = route('commesse.show', $commessa->id);

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->errorCorrection('M')
            ->generate($url);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
