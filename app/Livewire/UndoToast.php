<?php

namespace App\Livewire;

use App\Services\Undo\UndoService;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class UndoToast extends Component
{
    public array $toasts = [];

    #[On('action-completed')]
    public function onActionCompleted(string $message, ?int $activityId = null): void
    {
        $canUndo = false;
        if ($activityId) {
            $activity = Activity::find($activityId);
            if ($activity) {
                $canUndo = app(UndoService::class)->canUndo($activity, auth()->user());
            }
        }

        $this->toasts[] = [
            'key'        => uniqid(),
            'message'    => $message,
            'activityId' => $canUndo ? $activityId : null,
            'windowSec'  => (int) config('undo.window_minutes', 10) * 60,
        ];
    }

    public function undo(int $activityId): void
    {
        $activity = Activity::findOrFail($activityId);

        try {
            app(UndoService::class)->undo($activity, auth()->user());
            $this->removeToast($activityId);
            $this->dispatch('action-completed', message: 'Operazione annullata con successo.');
        } catch (\Throwable $e) {
            $this->removeToast($activityId);
            session()->flash('error', 'Annullamento fallito: ' . $e->getMessage());
        }
    }

    public function dismiss(string $key): void
    {
        $this->toasts = array_values(
            array_filter($this->toasts, fn ($t) => $t['key'] !== $key)
        );
    }

    private function removeToast(int $activityId): void
    {
        $this->toasts = array_values(
            array_filter($this->toasts, fn ($t) => $t['activityId'] !== $activityId)
        );
    }

    public function render()
    {
        return view('livewire.undo-toast');
    }
}
