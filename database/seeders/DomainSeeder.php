<?php

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = Partner::where('is_active', true)->get();
        
        if ($partners->isEmpty()) {
            $this->command->warn('No active partners found. Run PartnerSeeder first.');
            return;
        }

        foreach ($partners as $partner) {
            $clients = User::where('partner_id', $partner->id)
                ->where('role', 'client')
                ->get();

            if ($clients->isEmpty()) {
                $this->command->warn("No clients found for partner {$partner->name}. Skipping...");
                continue;
            }

            foreach ($clients->take(3) as $client) {
                $domainCount = rand(1, 3);
                
                for ($i = 0; $i < $domainCount; $i++) {
                    $domain = Domain::factory()
                        ->forClient($client)
                        ->forPartner($partner)
                        ->create([
                            'status' => $this->getRandomStatus(),
                        ]);

                    $domain->contacts()->create([
                        'type' => ContactType::Registrant,
                        'first_name' => fake()->firstName(),
                        'last_name' => fake()->lastName(),
                        'email' => $client->email,
                        'phone' => fake()->phoneNumber(),
                        'organization' => fake()->optional()->company(),
                        'address' => fake()->streetAddress(),
                        'city' => fake()->city(),
                        'state' => fake()->optional()->state(),
                        'postal_code' => fake()->postcode(),
                        'country' => fake()->countryCode(),
                    ]);

                    $domain->contacts()->create([
                        'type' => ContactType::Admin,
                        'first_name' => fake()->firstName(),
                        'last_name' => fake()->lastName(),
                        'email' => $client->email,
                        'phone' => fake()->phoneNumber(),
                        'organization' => fake()->optional()->company(),
                        'address' => fake()->streetAddress(),
                        'city' => fake()->city(),
                        'state' => fake()->optional()->state(),
                        'postal_code' => fake()->postcode(),
                        'country' => fake()->countryCode(),
                    ]);

                    for ($ns = 1; $ns <= rand(2, 4); $ns++) {
                        $domain->nameservers()->create([
                            'nameserver' => "ns{$ns}.nameserver.com",
                            'order' => $ns,
                        ]);
                    }

                    if ($domain->status === DomainStatus::Active) {
                        $domain->dnsRecords()->create([
                            'type' => 'A',
                            'name' => '@',
                            'value' => fake()->ipv4(),
                            'ttl' => 3600,
                        ]);

                        $domain->dnsRecords()->create([
                            'type' => 'A',
                            'name' => 'www',
                            'value' => fake()->ipv4(),
                            'ttl' => 3600,
                        ]);

                        $domain->dnsRecords()->create([
                            'type' => 'MX',
                            'name' => '@',
                            'value' => 'mail.' . $domain->name,
                            'ttl' => 3600,
                            'priority' => 10,
                        ]);
                    }
                }
            }

            $this->command->info("✓ Created domains for partner: {$partner->name}");
        }
    }

    private function getRandomStatus(): DomainStatus
    {
        $statuses = [
            DomainStatus::Active->value => 70,
            DomainStatus::PendingRegistration->value => 10,
            DomainStatus::Expired->value => 5,
            DomainStatus::GracePeriod->value => 5,
            DomainStatus::Redemption->value => 3,
            DomainStatus::Suspended->value => 5,
            DomainStatus::TransferredOut->value => 2,
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return DomainStatus::from($status);
            }
        }

        return DomainStatus::Active;
    }
}
