<?php

namespace App\Livewire\Activity;

use App\Services\Activity\ActivityFeedService;
use App\Services\Undo\UndoService;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class Feed extends Component
{
    use WithPagination;

    public string $filterUser = '';
    public string $filterType = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    public function updatingFilterUser(): void   { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterDateFrom(): void { $this->resetPage(); }
    public function updatingFilterDateTo(): void   { $this->resetPage(); }

    public function undo(int $activityId): void
    {
        $activity = Activity::findOrFail($activityId);

        try {
            app(UndoService::class)->undo($activity, auth()->user());
            session()->flash('success', 'Operazione annullata con successo.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Annullamento fallito: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = Activity::with(['causer', 'subject'])
            ->latest();

        if ($this->filterUser) {
            $query->where('causer_id', $this->filterUser);
        }

        if ($this->filterType) {
            $query->where('subject_type', $this->filterType);
        }

        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        $activities = $query->paginate(25);

        $feedService = app(ActivityFeedService::class);
        $undoService = app(UndoService::class);
        $user        = auth()->user();

        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        $entityTypes = [
            \App\Models\Commessa::class       => 'Commessa (OdL)',
            \App\Models\MovimentoMagazzino::class => 'Magazzino',
            \App\Models\CommessaRiga::class   => 'Riga OdL',
        ];

        return view('livewire.activity.feed', compact(
            'activities', 'feedService', 'undoService', 'user', 'users', 'entityTypes'
        ))->layout('layouts.app', ['title' => 'Attività']);
    }
}
