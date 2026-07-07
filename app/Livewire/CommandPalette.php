<?php

namespace App\Livewire;

use App\Services\GlobalSearchService;
use Livewire\Component;

class CommandPalette extends Component
{
    public bool $open = false;
    public string $query = '';
    public array $results = [];

    protected GlobalSearchService $searchService;

    public function boot(GlobalSearchService $searchService): void
    {
        $this->searchService = $searchService;
    }

    public function updatedQuery(): void
    {
        $this->results = $this->query !== ''
            ? $this->searchService->search($this->query)
            : [];
    }

    public function openPalette(): void
    {
        $this->open = true;
        $this->query = '';
        $this->results = [];
    }

    public function closePalette(): void
    {
        $this->open = false;
        $this->query = '';
        $this->results = [];
    }

    public function recordSelection(string $url, string $label, string $tipo): void
    {
        $recenti = session('palette_recenti', []);

        // Remove duplicate
        $recenti = array_filter($recenti, fn ($r) => $r['url'] !== $url);
        $recenti = array_values($recenti);

        array_unshift($recenti, ['url' => $url, 'label' => $label, 'tipo' => $tipo]);
        $recenti = array_slice($recenti, 0, 5);

        session(['palette_recenti' => $recenti]);

        $this->redirect($url);
    }

    public function getRecentiProperty(): array
    {
        return session('palette_recenti', []);
    }

    public function render()
    {
        return view('livewire.command-palette', [
            'recenti' => $this->recenti,
        ]);
    }
}
