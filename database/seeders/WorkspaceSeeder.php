<?php

namespace Database\Seeders;

use App\Models\Commessa;
use App\Models\User;
use App\Models\UserRecentItem;
use App\Models\UserShortcut;
use App\Models\UserSavedFilter;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();

        if (! $admin) {
            return;
        }

        // Shortcut demo
        $shortcuts = [
            ['label' => 'Commesse', 'url' => '/commesse', 'icon' => 'fas fa-clipboard-list', 'position' => 0],
            ['label' => 'Agenda', 'url' => '/agenda', 'icon' => 'fas fa-calendar-alt', 'position' => 1],
            ['label' => 'Articoli magazzino', 'url' => '/magazzino/articoli', 'icon' => 'fas fa-warehouse', 'position' => 2],
        ];

        foreach ($shortcuts as $sc) {
            UserShortcut::firstOrCreate(
                ['user_id' => $admin->id, 'url' => $sc['url']],
                $sc
            );
        }

        // Recent items demo (from existing commesse)
        $commesse = Commessa::latest()->limit(6)->get();
        foreach ($commesse as $i => $commessa) {
            UserRecentItem::updateOrCreate(
                [
                    'user_id'         => $admin->id,
                    'recordable_type' => Commessa::class,
                    'recordable_id'   => $commessa->id,
                ],
                [
                    'last_visited_at' => now()->subMinutes($i * 15),
                    'visits'          => rand(1, 10),
                ]
            );
        }

        // Saved filter demo
        UserSavedFilter::firstOrCreate(
            [
                'user_id'  => $admin->id,
                'page_key' => 'work-orders.index',
                'name'     => 'In lavorazione',
            ],
            [
                'filters'    => ['search' => '', 'filtroStato' => 'in_lavorazione', 'filtroTipo' => ''],
                'is_default' => true,
            ]
        );

        UserSavedFilter::firstOrCreate(
            [
                'user_id'  => $admin->id,
                'page_key' => 'work-orders.index',
                'name'     => 'Da consegnare',
            ],
            [
                'filters'    => ['search' => '', 'filtroStato' => 'completata', 'filtroTipo' => ''],
                'is_default' => false,
            ]
        );
    }
}
