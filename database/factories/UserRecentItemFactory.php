<?php

namespace Database\Factories;

use App\Models\Commessa;
use App\Models\User;
use App\Models\UserRecentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserRecentItemFactory extends Factory
{
    protected $model = UserRecentItem::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recordable_type' => Commessa::class,
            'recordable_id' => 1,
            'last_visited_at' => now(),
            'visits' => $this->faker->numberBetween(1, 20),
        ];
    }
}
