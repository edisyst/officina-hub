<?php

namespace App\Livewire\Concerns;

use App\Services\Workspace\RecentItemsService;
use Illuminate\Database\Eloquent\Model;

trait TracksRecentView
{
    protected function trackRecentView(Model $record): void
    {
        if (auth()->check()) {
            app(RecentItemsService::class)->track(auth()->user(), $record);
        }
    }
}
