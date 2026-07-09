<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

trait WithBulkSelection
{
    public array $selectedIds = [];
    public bool $selectAll = false;
    public bool $selectPage = false;

    public function updatedSelectPage(bool $value): void
    {
        $this->selectAll = false;
        $this->selectedIds = $value ? $this->getPageIds() : [];
    }

    public function updatedSelectedIds(): void
    {
        $this->selectPage = false;
        $this->selectAll = false;
    }

    public function selectAllResults(): void
    {
        $this->selectAll = true;
        $this->selectPage = true;
        $this->selectedIds = $this->getPageIds();
    }

    public function deselectAll(): void
    {
        $this->selectedIds = [];
        $this->selectPage = false;
        $this->selectAll = false;
    }

    #[Computed]
    public function selectionCount(): int
    {
        if ($this->selectAll) {
            return $this->getBulkQuery()->count();
        }
        return count($this->selectedIds);
    }

    /** @deprecated Use selectionCount computed property */
    public function getSelectionCount(): int
    {
        return $this->selectionCount();
    }

    /** IDs to operate on — either explicit list or all matching current filters */
    protected function resolveIds(): array
    {
        if ($this->selectAll) {
            return $this->getBulkQuery()->pluck('id')->toArray();
        }
        return $this->selectedIds;
    }

    /** Query matching current filter state (no pagination), used for selectAll */
    abstract protected function getBulkQuery(): Builder;

    /** IDs visible on the current page */
    abstract protected function getPageIds(): array;

    /** Throw AuthorizationException if not allowed */
    abstract protected function authorizeBulk(string $action): void;
}
