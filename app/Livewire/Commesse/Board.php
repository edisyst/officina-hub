<?php

namespace App\Livewire\Commesse;

use App\Actions\Commessa\AggiornaStatoAction;
use App\Enums\StatoCommessa;
use App\Enums\TipoRiga;
use App\Models\Commessa;
use App\Models\User;
use App\Services\Commesse\ReorderBoardService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Board extends Component
{
    public string $search = '';
    public ?int $filtroMeccanico = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        session(['commesse_vista' => 'board']);
    }

    public function moveCard(int $commessaId, string $newStatus, array $orderedIds): void
    {
        $this->errorMessage = null;

        $commessa = Commessa::findOrFail($commessaId);
        $nuovoStato = StatoCommessa::from($newStatus);

        try {
            DB::transaction(function () use ($commessa, $nuovoStato, $orderedIds) {
                if ($commessa->stato !== $nuovoStato) {
                    app(AggiornaStatoAction::class)->execute($commessa, $nuovoStato, auth()->user());
                    // Refresh stato after action (commessa updated in DB)
                    $commessa->refresh();
                }
                app(ReorderBoardService::class)->riordina($nuovoStato, $orderedIds);
            });
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first();
        }
    }

    public function render()
    {
        $staleDays = (int) config('board.stale_days', 5);

        // Single query + eager loading to avoid N+1 across 7 columns
        $allCommesse = Commessa::query()
            ->with(['cliente', 'veicolo', 'user', 'righe.articolo'])
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filtroMeccanico, fn($q) => $q->where('user_id', $this->filtroMeccanico))
            ->where(function ($q) {
                $q->whereNot('stato', StatoCommessa::Consegnata->value)
                  ->orWhere('data_consegna', '>=', now()->subDays(7));
            })
            ->orderBy('board_position')
            ->orderBy('data_ingresso')
            ->get();

        $grouped = $allCommesse->groupBy(fn($c) => $c->stato->value);

        $colonne = [];
        foreach (StatoCommessa::cases() as $stato) {
            $commesse = $grouped->get($stato->value, collect());
            $oreStimate = $commesse->sum(function ($c) {
                return $c->righe
                    ->where('tipo', TipoRiga::Manodopera)
                    ->sum('quantita');
            });

            $colonne[$stato->value] = [
                'stato'       => $stato,
                'commesse'    => $commesse,
                'count'       => $commesse->count(),
                'ore_stimate' => round((float) $oreStimate, 1),
            ];
        }

        $meccanici = User::role(['admin', 'meccanico', 'accettatore'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.commesse.board', [
            'colonne'    => $colonne,
            'meccanici'  => $meccanici,
            'staleDays'  => $staleDays,
        ])->layout('layouts.app', ['title' => 'Board Officina']);
    }
}
