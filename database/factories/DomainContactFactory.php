<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainContact>
 */
class DomainContactFactory extends Factory
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
            'type' => fake()->randomElement(ContactType::cases()),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'organization' => fake()->optional()->company(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->optional()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->countryCode(),
        ];
    }

    public function registrant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactType::Registrant,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactType::Admin,
        ]);
    }

    public function tech(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactType::Tech,
        ]);
    }

    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ContactType::Billing,
        ]);
    }
}
