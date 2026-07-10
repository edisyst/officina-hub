<?php

namespace App\Services\Undo\Handlers;

use App\Actions\Magazzino\CaricoManualeAction;
use App\Contracts\UndoHandler;
use App\Enums\TipoMovimento;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class StockMovementUndoHandler implements UndoHandler
{
    public function supports(Activity $activity): bool
    {
        return $activity->subject_type === MovimentoMagazzino::class
            && $activity->event === 'created';
    }

    public function undo(Activity $activity, User $user): Activity
    {
        /** @var MovimentoMagazzino $movimento */
        $movimento = MovimentoMagazzino::with('articolo')->findOrFail($activity->subject_id);

        $tipoInverso = $movimento->tipo->aumentaGiacenza()
            ? TipoMovimento::Scarico
            : TipoMovimento::Carico;

        $compensazione = app(CaricoManualeAction::class)->execute(
            articolo: $movimento->articolo,
            tipo: $tipoInverso,
            quantita: $movimento->quantita,
            utente: $user,
            note: "Storno movimento #{$movimento->id}",
        );

        $act = Activity::where('subject_type', MovimentoMagazzino::class)
            ->where('subject_id', $compensazione->id)
            ->latest('id')
            ->firstOrFail();

        return $act;
    }
}
