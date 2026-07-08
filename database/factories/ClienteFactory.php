<?php

namespace Database\Factories;

use App\Enums\TipoCliente;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'tipo'     => TipoCliente::Fisica,
            'nome'     => $this->faker->firstName(),
            'cognome'  => $this->faker->lastName(),
            'email'    => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->phoneNumber(),
        ];
    }
}
