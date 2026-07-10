<?php

namespace App\Livewire\Workspace;

use App\Models\UserShortcut;
use Livewire\Component;

class WorkspaceBar extends Component
{
    public string $currentUrl = '';
    public string $currentTitle = '';
    public bool $isStarred = false;
    public string $editLabel = '';
    public bool $showLabelPopover = false;

    public function mount(string $url = '', string $title = ''): void
    {
        $this->currentUrl   = $url ?: request()->url();
        $this->currentTitle = $title;
        $this->editLabel    = $title;
        $this->refreshStarred();
    }

    public function toggle(): void
    {
        if ($this->isStarred) {
            $this->removeShortcut();
        } else {
            $this->showLabelPopover = true;
        }
    }

    public function saveShortcut(): void
    {
        $this->validate([
            'editLabel' => 'required|string|max:100',
        ]);

        $path = parse_url($this->currentUrl, PHP_URL_PATH);

        UserShortcut::updateOrCreate(
            ['user_id' => auth()->id(), 'url' => $path],
            [
                'label'    => $this->editLabel,
                'icon'     => 'fas fa-star',
                'position' => UserShortcut::where('user_id', auth()->id())->max('position') + 1,
            ]
        );

        $this->showLabelPopover = false;
        $this->isStarred = true;
    }

    public function removeShortcut(): void
    {
        $path = parse_url($this->currentUrl, PHP_URL_PATH);

        UserShortcut::where('user_id', auth()->id())
            ->where('url', $path)
            ->delete();

        $this->showLabelPopover = false;
        $this->isStarred = false;
    }

    public function cancelPopover(): void
    {
        $this->showLabelPopover = false;
        $this->editLabel = $this->currentTitle;
    }

    private function refreshStarred(): void
    {
        if (! auth()->check()) {
            return;
        }

        $path = parse_url($this->currentUrl, PHP_URL_PATH);

        $this->isStarred = UserShortcut::where('user_id', auth()->id())
            ->where('url', $path)
            ->exists();
    }

    public function render()
    {
        return view('livewire.workspace.workspace-bar');
    }
}
