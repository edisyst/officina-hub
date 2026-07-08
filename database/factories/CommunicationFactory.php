<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Communication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunicationFactory extends Factory
{
    protected $model = Communication::class;

    public function definition(): array
    {
        $channel   = $this->faker->randomElement(['phone', 'note', 'email', 'whatsapp', 'sms']);
        $direction = $this->faker->randomElement(['inbound', 'outbound']);

        return [
            'customer_id'   => Cliente::factory(),
            'work_order_id' => null,
            'user_id'       => User::factory(),
            'channel'       => $channel,
            'direction'     => $direction,
            'subject'       => $this->faker->optional(0.3)->sentence(4),
            'body'          => $this->faker->paragraph(),
            'occurred_at'   => $this->faker->dateTimeBetween('-6 months', 'now'),
            'meta'          => null,
        ];
    }

    public function forCustomer(Cliente $cliente): static
    {
        return $this->state(['customer_id' => $cliente->id]);
    }

    public function forWorkOrder(Commessa $commessa): static
    {
        return $this->state([
            'customer_id'   => $commessa->cliente_id,
            'work_order_id' => $commessa->id,
        ]);
    }

    public function phone(): static
    {
        return $this->state(['channel' => 'phone']);
    }

    public function note(): static
    {
        return $this->state(['channel' => 'note', 'direction' => 'outbound']);
    }
}
