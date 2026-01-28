<?php

namespace Database\Factories;

use App\Models\Registrar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tld>
 */
class TldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extensions = ['com', 'net', 'org', 'io', 'app', 'dev', 'tech', 'store', 'online', 'xyz'];

        return [
            'registrar_id' => Registrar::factory(),
            'extension' => fake()->randomElement($extensions),
            'min_years' => 1,
            'max_years' => 10,
            'supports_dns' => fake()->boolean(80),
            'supports_whois_privacy' => fake()->boolean(70),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function com(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => 'com',
            'supports_dns' => true,
            'supports_whois_privacy' => true,
        ]);
    }

    public function net(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => 'net',
            'supports_dns' => true,
            'supports_whois_privacy' => true,
        ]);
    }

    public function io(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => 'io',
            'supports_dns' => true,
            'supports_whois_privacy' => false,
        ]);
    }
}
