<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerDomain>
 */
class PartnerDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'partner_id' => \App\Models\Partner::factory(),
            'domain' => fake()->unique()->domainName(),
            'is_primary' => false,
            'is_verified' => fake()->boolean(70),
            'dns_status' => fake()->randomElement(['pending', 'verified', 'failed']),
            'ssl_status' => fake()->randomElement(['pending', 'issued', 'failed', 'expired']),
            'verified_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'ssl_issued_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'is_verified' => true,
            'dns_status' => 'verified',
            'ssl_status' => 'issued',
            'verified_at' => now()->subDays(rand(1, 30)),
            'ssl_issued_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'dns_status' => 'verified',
            'verified_at' => now()->subDays(rand(1, 30)),
        ]);
    }
}
