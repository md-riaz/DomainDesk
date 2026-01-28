<?php

namespace Database\Factories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainNameserver>
 */
class DomainNameserverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory(),
            'nameserver' => 'ns' . fake()->numberBetween(1, 4) . '.example.com',
            'order' => 1,
        ];
    }

    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
            'nameserver' => 'ns' . $order . '.example.com',
        ]);
    }
}
