<?php

namespace Database\Factories;

use App\Enums\PriceAction;
use App\Models\Tld;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TldPrice>
 */
class TldPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tld_id' => Tld::factory(),
            'action' => fake()->randomElement(PriceAction::cases())->value,
            'years' => fake()->numberBetween(1, 5),
            'price' => fake()->randomFloat(2, 5, 100),
            'effective_date' => now()->toDateString(),
        ];
    }

    public function register(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => PriceAction::REGISTER->value,
            'price' => fake()->randomFloat(2, 10, 50),
        ]);
    }

    public function renew(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => PriceAction::RENEW->value,
            'price' => fake()->randomFloat(2, 10, 50),
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => PriceAction::TRANSFER->value,
            'price' => fake()->randomFloat(2, 10, 50),
        ]);
    }

    public function effective(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => $date,
        ]);
    }

    public function forTld(Tld $tld): static
    {
        return $this->state(fn (array $attributes) => [
            'tld_id' => $tld->id,
        ]);
    }
}
