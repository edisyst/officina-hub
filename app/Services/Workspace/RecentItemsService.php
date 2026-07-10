<?php

namespace App\Services\Workspace;

use App\Models\User;
use App\Models\UserRecentItem;
use App\Presenters\RecordablePresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecentItemsService
{
    public function track(User $user, Model $record): void
    {
        DB::transaction(function () use ($user, $record) {
            $item = UserRecentItem::where([
                'user_id'         => $user->id,
                'recordable_type' => $record->getMorphClass(),
                'recordable_id'   => $record->getKey(),
            ])->first();

            if ($item) {
                $item->increment('visits');
                $item->update(['last_visited_at' => now()]);
            } else {
                UserRecentItem::create([
                    'user_id'         => $user->id,
                    'recordable_type' => $record->getMorphClass(),
                    'recordable_id'   => $record->getKey(),
                    'last_visited_at' => now(),
                    'visits'          => 1,
                ]);
            }

            $this->prune($user);
        });
    }

    public function recent(User $user, int $limit = 10): Collection
    {
        return UserRecentItem::with('recordable')
            ->where('user_id', $user->id)
            ->orderByDesc('last_visited_at')
            ->limit($limit)
            ->get()
            ->filter(fn (UserRecentItem $item) => $item->recordable !== null)
            ->map(fn (UserRecentItem $item) => [
                'id'             => $item->id,
                'label'          => RecordablePresenter::label($item->recordable),
                'url'            => RecordablePresenter::url($item->recordable),
                'icon'           => RecordablePresenter::icon($item->recordable),
                'tipo'           => RecordablePresenter::tipo($item->recordable),
                'last_visited_at' => $item->last_visited_at,
                'visits'         => $item->visits,
            ])
            ->values();
    }

    private function prune(User $user): void
    {
        $limit = config('workspace.recent_limit', 20);

        $keep = UserRecentItem::where('user_id', $user->id)
            ->orderByDesc('last_visited_at')
            ->limit($limit)
            ->pluck('id');

        UserRecentItem::where('user_id', $user->id)
            ->whereNotIn('id', $keep)
            ->delete();
    }
}
