<?php

namespace App\Livewire\Workspace;

use App\Models\UserShortcut;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Le mie scorciatoie')]
class Shortcuts extends Component
{
    public array $shortcuts = [];
    public ?int $editingId = null;
    public string $editLabel = '';

    public function mount(): void
    {
        $this->loadShortcuts();
    }

    public function loadShortcuts(): void
    {
        $this->shortcuts = UserShortcut::where('user_id', auth()->id())
            ->orderBy('position')
            ->get()
            ->toArray();
    }

    public function startEdit(int $id): void
    {
        $sc = collect($this->shortcuts)->firstWhere('id', $id);
        if ($sc) {
            $this->editingId = $id;
            $this->editLabel = $sc['label'];
        }
    }

    public function saveEdit(): void
    {
        $this->validate(['editLabel' => 'required|string|max:100']);

        UserShortcut::where('user_id', auth()->id())
            ->where('id', $this->editingId)
            ->update(['label' => $this->editLabel]);

        $this->editingId = null;
        $this->editLabel = '';
        $this->loadShortcuts();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editLabel = '';
    }

    public function delete(int $id): void
    {
        UserShortcut::where('user_id', auth()->id())
            ->where('id', $id)
            ->delete();

        $this->loadShortcuts();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            UserShortcut::where('user_id', auth()->id())
                ->where('id', (int) $id)
                ->update(['position' => $position]);
        }

        $this->loadShortcuts();
    }

    public function moveUp(int $id): void
    {
        $ids = collect($this->shortcuts)->pluck('id')->toArray();
        $idx = array_search($id, $ids);

        if ($idx > 0) {
            [$ids[$idx - 1], $ids[$idx]] = [$ids[$idx], $ids[$idx - 1]];
            $this->reorder($ids);
        }
    }

    public function moveDown(int $id): void
    {
        $ids = collect($this->shortcuts)->pluck('id')->toArray();
        $idx = array_search($id, $ids);

        if ($idx !== false && $idx < count($ids) - 1) {
            [$ids[$idx], $ids[$idx + 1]] = [$ids[$idx + 1], $ids[$idx]];
            $this->reorder($ids);
        }
    }

    public function render()
    {
        return view('livewire.workspace.shortcuts')
            ->layout('layouts.app', ['title' => 'Le mie scorciatoie']);
    }
}
