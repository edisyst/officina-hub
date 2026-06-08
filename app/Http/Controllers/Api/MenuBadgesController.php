<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatoCommessa;
use App\Enums\StatoDocumento;
use App\Enums\StatoPneumatico;
use App\Enums\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Models\Articolo;
use App\Models\Commessa;
use App\Models\Documento;
use App\Models\Pneumatico;
use App\Models\PrestitoCortesia;
use App\Models\Scadenza;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class MenuBadgesController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $badges = Cache::remember('menu_badges_' . $user->id, 120, function () use ($user) {
            $b = [
                'commesse_in_lavorazione' => 0,
                'articoli_sotto_scorta'   => 0,
                'fatture_scadute'         => 0,
                'scadenze_imminenti'      => 0,
                'deposito_da_ritirare'    => 0,
                'cortesia_in_ritardo'     => 0,
            ];

            if ($user->hasAnyRole(['admin', 'accettatore', 'cassa'])) {
                $b['commesse_in_lavorazione'] = Commessa::where('stato', StatoCommessa::InLavorazione)->count();
            }

            if ($user->hasAnyRole(['admin', 'accettatore', 'cassa'])) {
                $b['articoli_sotto_scorta'] = Articolo::attivi()->sottoScorta()->count();
            }

            if ($user->hasAnyRole(['admin', 'cassa'])) {
                $b['fatture_scadute'] = Documento::where('tipo', TipoDocumento::Fattura)
                    ->whereIn('stato', [StatoDocumento::Emessa, StatoDocumento::InviataSdi, StatoDocumento::AccettataSdi])
                    ->where('data_scadenza', '<', now()->subDays(30))
                    ->count();
            }

            if ($user->hasAnyRole(['admin', 'accettatore'])) {
                $b['scadenze_imminenti'] = Scadenza::where('notifica_disabilitata', false)
                    ->where('data_scadenza', '>=', now()->startOfDay())
                    ->where('data_scadenza', '<=', now()->addDays(15))
                    ->count();
            }

            // Badge deposito: set in deposito da più di 180 giorni
            if ($user->hasAnyRole(['admin', 'accettatore', 'cassa'])) {
                $b['deposito_da_ritirare'] = Pneumatico::where('stato', StatoPneumatico::InDeposito)
                    ->whereHas('movimenti', fn($q) =>
                        $q->where('data_azione', '<=', now()->subDays(180)->toDateString())
                    )
                    ->count();
            }

            // Badge cortesia: prestiti in ritardo
            if ($user->hasAnyRole(['admin', 'accettatore'])) {
                $b['cortesia_in_ritardo'] = PrestitoCortesia::inRitardo()->count();
            }

            return $b;
        });

        return response()->json($badges);
    }
}
