<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserShortcut;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserShortcutFactory extends Factory
{
    protected $model = UserShortcut::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => $this->faker->words(3, true),
            'url' => '/' . $this->faker->slug(),
            'icon' => 'fas fa-star',
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }
}
