<?php

namespace App\Livewire;

use App\Services\TechBoardService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TechBoard extends Component
{
    #[Computed]
    public function lavorazioni(): \Illuminate\Support\Collection
    {
        return app(TechBoardService::class)->lavorazioniAttive();
    }

    #[Computed]
    public function sospese(): \Illuminate\Support\Collection
    {
        return app(TechBoardService::class)->commesseSospese();
    }

    #[Computed]
    public function appuntamenti(): \Illuminate\Support\Collection
    {
        return app(TechBoardService::class)->prossimiAppuntamenti();
    }

    #[Computed]
    public function hasEstimated(): bool
    {
        return app(TechBoardService::class)->hasEstimatedMinutes();
    }

    public function render()
    {
        return view('livewire.tech-board')
            ->layout('layouts.board');
    }
}
