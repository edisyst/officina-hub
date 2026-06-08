<?php

namespace App\Services;

use App\Models\Pneumatico;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EtichettaDepositoService
{
    public function genera(Pneumatico $pneumatico): \Illuminate\Http\Response
    {
        $pneumatico->load(['veicolo', 'cliente', 'movimenti']);

        $codice     = $pneumatico->codiceEtichetta();
        $qrCodeSvg  = QrCode::size(80)->generate(route('deposito.qr', $codice));
        $formato    = setting('etichetta_deposito_formato', 'A6');

        $ultimoDeposito = $pneumatico->movimenti
            ->where('azione->value', 'deposito')
            ->first()
            ?? $pneumatico->movimenti->first();

        $pdf = Pdf::loadView('pdf.etichetta-deposito', compact(
            'pneumatico', 'codice', 'qrCodeSvg', 'ultimoDeposito'
        ));

        if ($formato === 'adesivo') {
            $pdf->setPaper([0, 0, 283.46, 141.73]); // 100×50mm in punti
        } else {
            $pdf->setPaper('A6', 'portrait');
        }

        return $pdf->download("etichetta-{$codice}.pdf");
    }

    public function generaMultiple(array $ids): \Illuminate\Http\Response
    {
        $pneumatici = Pneumatico::whereIn('id', $ids)
            ->with(['veicolo', 'cliente', 'movimenti'])
            ->get();

        $formato   = setting('etichetta_deposito_formato', 'A6');
        $etichette = $pneumatici->map(fn($p) => [
            'pneumatico'     => $p,
            'codice'         => $p->codiceEtichetta(),
            'qrCodeSvg'      => QrCode::size(80)->generate(route('deposito.qr', $p->codiceEtichetta())),
            'ultimoDeposito' => $p->movimenti->first(),
        ]);

        $pdf = Pdf::loadView('pdf.etichette-deposito-multiple', compact('etichette'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('etichette-deposito.pdf');
    }
}
