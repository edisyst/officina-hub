<?php

namespace App\Services\Workspace;

use App\Models\User;
use App\Models\UserSavedFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SavedFilterService
{
    public function list(User $user, string $pageKey): Collection
    {
        return UserSavedFilter::where('user_id', $user->id)
            ->where('page_key', $pageKey)
            ->orderBy('name')
            ->get();
    }

    public function save(User $user, string $pageKey, string $name, array $filters): UserSavedFilter
    {
        return UserSavedFilter::updateOrCreate(
            [
                'user_id'  => $user->id,
                'page_key' => $pageKey,
                'name'     => $name,
            ],
            ['filters' => $filters]
        );
    }

    public function getDefault(User $user, string $pageKey): ?UserSavedFilter
    {
        return UserSavedFilter::where('user_id', $user->id)
            ->where('page_key', $pageKey)
            ->where('is_default', true)
            ->first();
    }

    public function setDefault(User $user, string $pageKey, int $filterId): void
    {
        DB::transaction(function () use ($user, $pageKey, $filterId) {
            UserSavedFilter::where('user_id', $user->id)
                ->where('page_key', $pageKey)
                ->update(['is_default' => false]);

            UserSavedFilter::where('user_id', $user->id)
                ->where('page_key', $pageKey)
                ->where('id', $filterId)
                ->update(['is_default' => true]);
        });
    }

    public function clearDefault(User $user, string $pageKey): void
    {
        UserSavedFilter::where('user_id', $user->id)
            ->where('page_key', $pageKey)
            ->update(['is_default' => false]);
    }

    public function delete(User $user, int $filterId): void
    {
        UserSavedFilter::where('user_id', $user->id)
            ->where('id', $filterId)
            ->delete();
    }
}
