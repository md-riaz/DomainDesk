<?php

namespace Database\Factories;

use App\Enums\DnsRecordType;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainDnsRecord>
 */
class DomainDnsRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DnsRecordType::cases());

        return [
            'domain_id' => Domain::factory(),
            'type' => $type,
            'name' => fake()->randomElement(['@', 'www', 'mail', 'ftp', '*']),
            'value' => $this->generateValueForType($type),
            'ttl' => fake()->randomElement([300, 600, 1800, 3600, 7200, 14400, 28800, 43200, 86400]),
            'priority' => $type === DnsRecordType::MX ? fake()->numberBetween(10, 50) : null,
        ];
    }

    protected function generateValueForType(DnsRecordType $type): string
    {
        return match($type) {
            DnsRecordType::A => fake()->ipv4(),
            DnsRecordType::AAAA => fake()->ipv6(),
            DnsRecordType::CNAME => fake()->domainName(),
            DnsRecordType::MX => 'mail.' . fake()->domainName(),
            DnsRecordType::TXT => 'v=spf1 include:_spf.example.com ~all',
            DnsRecordType::NS => 'ns1.' . fake()->domainName(),
        };
    }

    public function aRecord(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DnsRecordType::A,
            'value' => fake()->ipv4(),
            'priority' => null,
        ]);
    }

    public function mxRecord(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DnsRecordType::MX,
            'value' => 'mail.' . fake()->domainName(),
            'priority' => fake()->numberBetween(10, 50),
        ]);
    }
}
