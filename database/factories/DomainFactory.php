<?php

namespace Database\Factories;

use App\Enums\DomainStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Only use statuses that are in the database enum constraint
        $validStatuses = [
            DomainStatus::PendingRegistration,
            DomainStatus::Active,
            DomainStatus::Expired,
            DomainStatus::GracePeriod,
            DomainStatus::Redemption,
            DomainStatus::Suspended,
            DomainStatus::TransferredOut,
        ];
        
        return [
            'name' => fake()->domainName(),
            'client_id' => User::factory(),
            'partner_id' => Partner::factory(),
            'registrar_id' => null,
            'status' => fake()->randomElement($validStatuses),
            'registered_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'expires_at' => fake()->dateTimeBetween('now', '+2 years'),
            'auto_renew' => fake()->boolean(70),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DomainStatus::Active,
            'registered_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
            'expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    public function expiring(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DomainStatus::Active,
            'registered_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
            'expires_at' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DomainStatus::Expired,
            'registered_at' => fake()->dateTimeBetween('-2 years', '-1 year'),
            'expires_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function forClient(User $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'partner_id' => $client->partner_id,
        ]);
    }

    public function forPartner(Partner $partner): static
    {
        return $this->state(fn (array $attributes) => [
            'partner_id' => $partner->id,
        ]);
    }
}
