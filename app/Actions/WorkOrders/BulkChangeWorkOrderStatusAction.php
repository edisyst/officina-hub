<?php

namespace App\Actions\WorkOrders;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Enums\StatoCommessa;
use App\Models\Commessa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BulkChangeWorkOrderStatusAction
{
    public function __construct(private AggiornaStatoAction $statoAction) {}

    /**
     * Transitions each OdL independently; a failure on one does not block others.
     *
     * @return array{success: int[], skipped: array<int, array{id: int, numero: string, motivo: string}>}
     */
    public function execute(array $ids, StatoCommessa $nuovoStato, User $utente, ?string $nota = null): array
    {
        $result = ['success' => [], 'skipped' => []];

        foreach (array_chunk($ids, 50) as $chunk) {
            foreach (Commessa::whereIn('id', $chunk)->get() as $commessa) {
                try {
                    $this->statoAction->execute($commessa, $nuovoStato, $utente, $nota);
                    $result['success'][] = $commessa->id;
                } catch (ValidationException $e) {
                    $result['skipped'][] = [
                        'id'     => $commessa->id,
                        'numero' => $commessa->numero,
                        'motivo' => collect($e->errors())->flatten()->first() ?? 'Transizione non ammessa',
                    ];
                } catch (\Throwable $e) {
                    $result['skipped'][] = [
                        'id'     => $commessa->id,
                        'numero' => $commessa->numero,
                        'motivo' => 'Errore: ' . $e->getMessage(),
                    ];
                }
            }
        }

        return $result;
    }
}
