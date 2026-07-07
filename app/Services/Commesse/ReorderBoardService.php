<?php

namespace App\Services\Commesse;

use App\Enums\StatoCommessa;
use Illuminate\Support\Facades\DB;

class ReorderBoardService
{
    /**
     * Ricalcola board_position di un insieme di commesse in una colonna.
     * I IDs devono essere passati nell'ordine desiderato.
     */
    public function riordina(StatoCommessa $stato, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        DB::transaction(function () use ($stato, $ids) {
            foreach ($ids as $i => $id) {
                DB::table('commesse')
                    ->where('id', (int) $id)
                    ->where('stato', $stato->value)
                    ->update(['board_position' => $i + 1]);
            }
        });
    }
}
