<?php

namespace App\Livewire\Activity;

use App\Services\Activity\ActivityFeedService;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class FeedWidget extends Component
{
    public function render()
    {
        $activities  = Activity::with(['causer', 'subject'])->latest()->limit(10)->get();
        $feedService = app(ActivityFeedService::class);

        return view('livewire.activity.feed-widget', compact('activities', 'feedService'));
    }
}
