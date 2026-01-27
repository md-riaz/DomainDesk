<?php

namespace Tests\Feature;

use App\Enums\ContactType;
use App\Enums\DomainStatus;
use App\Enums\Role;
use App\Models\Domain;
use App\Models\DomainContact;
use App\Models\DomainNameserver;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainManagementTest extends TestCase
{
    use RefreshDatabase;

    private Partner $partner;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->active()->create();
        $this->client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_domain_requires_client_and_partner(): void
    {
        $domain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_domain_belongs_to_client_and_partner(): void
    {
        $domain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertInstanceOf(User::class, $domain->client);
        $this->assertInstanceOf(Partner::class, $domain->partner);
        $this->assertEquals($this->client->id, $domain->client->id);
        $this->assertEquals($this->partner->id, $domain->partner->id);
    }

    public function test_domain_can_have_contacts(): void
    {
        $domain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $domain->contacts()->create([
            'type' => ContactType::Registrant,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'country' => 'US',
        ]);

        $this->assertCount(1, $domain->contacts);
        $this->assertEquals(ContactType::Registrant, $domain->contacts->first()->type);
    }

    public function test_domain_can_have_nameservers(): void
    {
        $domain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        for ($i = 1; $i <= 2; $i++) {
            $domain->nameservers()->create([
                'nameserver' => "ns{$i}.example.com",
                'order' => $i,
            ]);
        }

        $this->assertCount(2, $domain->nameservers);
        $this->assertEquals('ns1.example.com', $domain->nameservers->first()->nameserver);
    }

    public function test_nameserver_order_must_be_between_1_and_4(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nameserver order must be between 1 and 4');

        $domain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $domain->nameservers()->create([
            'nameserver' => 'ns5.example.com',
            'order' => 5, // Invalid
        ]);
    }

    public function test_domain_scope_for_partner(): void
    {
        Domain::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $otherPartner = Partner::factory()->create();
        $otherClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $otherPartner->id,
        ]);
        Domain::factory()->count(2)->create([
            'client_id' => $otherClient->id,
            'partner_id' => $otherPartner->id,
        ]);

        $this->assertCount(3, Domain::forPartner($this->partner->id)->get());
        $this->assertCount(2, Domain::forPartner($otherPartner->id)->get());
    }

    public function test_domain_active_scope(): void
    {
        Domain::factory()->active()->count(2)->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        Domain::factory()->expired()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertCount(2, Domain::active()->get());
    }

    public function test_domain_can_have_dns_records(): void
    {
        $domain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $domain->dnsRecords()->create([
            'type' => 'A',
            'name' => '@',
            'value' => '192.168.1.1',
            'ttl' => 3600,
        ]);

        $this->assertCount(1, $domain->dnsRecords);
    }

    public function test_domain_status_enum(): void
    {
        $domain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'status' => DomainStatus::Active,
        ]);

        $this->assertEquals(DomainStatus::Active, $domain->status);
        $this->assertTrue($domain->status->isActive());
        $this->assertTrue($domain->status->isRenewable());
    }
}
