<?php

namespace App\Livewire\Concerns;

use App\Models\UserSavedFilter;
use App\Services\Workspace\SavedFilterService;
use Illuminate\Support\Collection;

/**
 * Attach to Livewire table components.
 * Component must define:
 *   protected string $pageKey;
 *   protected array $filterWhitelist;
 * Component mount() must call $this->initSavedFilters() after setting its own state.
 */
trait WithSavedFilters
{
    public array $savedFiltersData = [];
    public string $newFilterName = '';
    public bool $showSaveFilterModal = false;

    public function initSavedFilters(): void
    {
        $this->loadSavedFiltersData();
        $this->applyDefaultFilter();
    }

    public function loadSavedFiltersData(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->savedFiltersData = app(SavedFilterService::class)
            ->list(auth()->user(), $this->pageKey)
            ->toArray();
    }

    public function currentFilters(): array
    {
        return collect($this->filterWhitelist)
            ->mapWithKeys(fn (string $key) => [$key => $this->$key])
            ->toArray();
    }

    public function applyFilters(array $filters): void
    {
        foreach ($this->filterWhitelist as $key) {
            if (array_key_exists($key, $filters)) {
                $this->$key = $filters[$key];
            }
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function applySavedFilter(int $id): void
    {
        $filter = UserSavedFilter::where('user_id', auth()->id())
            ->where('page_key', $this->pageKey)
            ->findOrFail($id);

        $this->applyFilters($filter->filters);
    }

    public function saveCurrentFilters(): void
    {
        $this->validate(['newFilterName' => 'required|string|max:100']);

        app(SavedFilterService::class)->save(
            auth()->user(),
            $this->pageKey,
            $this->newFilterName,
            $this->currentFilters()
        );

        $this->newFilterName = '';
        $this->showSaveFilterModal = false;
        $this->loadSavedFiltersData();
    }

    public function deleteSavedFilter(int $id): void
    {
        app(SavedFilterService::class)->delete(auth()->user(), $id);
        $this->loadSavedFiltersData();
    }

    public function setDefaultFilter(int $id): void
    {
        app(SavedFilterService::class)->setDefault(auth()->user(), $this->pageKey, $id);
        $this->loadSavedFiltersData();
    }

    public function clearDefaultFilter(): void
    {
        app(SavedFilterService::class)->clearDefault(auth()->user(), $this->pageKey);
        $this->loadSavedFiltersData();
    }

    protected function applyDefaultFilter(): void
    {
        if (! auth()->check() || $this->hasActiveFilters()) {
            return;
        }

        $default = app(SavedFilterService::class)->getDefault(auth()->user(), $this->pageKey);

        if ($default) {
            $this->applyFilters($default->filters);
        }
    }

    protected function hasActiveFilters(): bool
    {
        foreach ($this->filterWhitelist as $key) {
            $val = $this->$key;
            if ($val !== '' && $val !== null && $val !== false) {
                return true;
            }
        }

        return false;
    }
}
