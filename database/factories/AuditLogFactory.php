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
        $actions = ['created', 'updated', 'deleted', 'soft_deleted'];
        
        return [
            'user_id' => User::factory(),
            'partner_id' => Partner::factory(),
            'action' => fake()->randomElement($actions),
            'auditable_type' => Domain::class,
            'auditable_id' => Domain::factory(),
            'old_values' => fake()->randomElement([null, ['status' => 'active']]),
            'new_values' => fake()->randomElement([null, ['status' => 'expired']]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
