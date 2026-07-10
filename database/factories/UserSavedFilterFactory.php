<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSavedFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSavedFilterFactory extends Factory
{
    protected $model = UserSavedFilter::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'page_key' => 'work-orders.index',
            'name' => $this->faker->words(2, true),
            'filters' => ['search' => '', 'filtroStato' => 'in_lavorazione'],
            'is_default' => false,
        ];
    }
}
