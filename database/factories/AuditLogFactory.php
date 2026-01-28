<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\User;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'partner_id' => Partner::factory(),
            'action' => 'created',
            'auditable_type' => Domain::class,
            'auditable_id' => Domain::factory(),
            'old_values' => null,
            'new_values' => ['status' => 'active', 'name' => fake()->domainName()],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'updated',
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'expired'],
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'deleted',
            'old_values' => ['status' => 'active', 'name' => fake()->domainName()],
            'new_values' => null,
        ]);
    }
}
