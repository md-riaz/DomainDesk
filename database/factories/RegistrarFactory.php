<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registrar>
 */
class RegistrarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company() . ' Registrar';
        $slug = strtolower(str_replace(' ', '-', $name));

        return [
            'name' => $name,
            'slug' => $slug,
            'api_class' => 'App\\Services\\Registrar\\MockRegistrar',
            'credentials' => [
                'api_key' => fake()->uuid(),
                'api_secret' => fake()->sha256(),
            ],
            'is_active' => true,
            'is_default' => false,
            'last_sync_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function mock(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Mock Registrar',
            'slug' => 'mock',
            'api_class' => 'App\\Services\\Registrar\\MockRegistrar',
        ]);
    }

    public function resellerclub(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'ResellerClub',
            'slug' => 'resellerclub',
            'api_class' => 'App\\Services\\Registrar\\ResellerClubRegistrar',
        ]);
    }
}
