<?php

namespace Database\Factories;

use App\Models\MatricePrezzoScaglione;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatricePrezzoScaglioneFactory extends Factory
{
    protected $model = MatricePrezzoScaglione::class;

    public function definition(): array
    {
        return [
            'costo_da'      => 0.00,
            'costo_a'       => null,
            'markup_percent' => 50.00,
            'arrotondamento' => 'none',
        ];
    }
}
