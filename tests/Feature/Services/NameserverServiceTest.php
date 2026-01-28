<?php

namespace Tests\Feature\Services;

use App\Models\Domain;
use App\Models\DomainNameserver;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\User;
use App\Services\NameserverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NameserverServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NameserverService $service;
    protected Domain $domain;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(NameserverService::class);
        
        // Create test data
        $partner = Partner::factory()->create();
        $registrar = Registrar::factory()->create(['slug' => 'mock']);
        $this->user = User::factory()->client()->create(['partner_id' => $partner->id]);
        
        $this->domain = Domain::factory()->active()->create([
            'partner_id' => $partner->id,
            'registrar_id' => $registrar->id,
            'client_id' => $this->user->id,
        ]);
    }

    public function test_update_nameservers_successfully()
    {
        $nameservers = [
            'ns1.example.com',
            'ns2.example.com',
        ];

        $result = $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully', $result['message']);
        
        $this->assertDatabaseHas('domain_nameservers', [
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.example.com',
            'order' => 1,
        ]);
        
        $this->assertDatabaseHas('domain_nameservers', [
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns2.example.com',
            'order' => 2,
        ]);
    }

    public function test_update_nameservers_with_four_nameservers()
    {
        $nameservers = [
            'ns1.example.com',
            'ns2.example.com',
            'ns3.example.com',
            'ns4.example.com',
        ];

        $result = $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertCount(4, $this->domain->fresh()->nameservers);
    }

    public function test_update_nameservers_normalizes_case()
    {
        $nameservers = [
            'NS1.EXAMPLE.COM',
            'NS2.Example.Com',
        ];

        $result = $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);

        $this->assertTrue($result['success']);
        
        $this->assertDatabaseHas('domain_nameservers', [
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.example.com',
        ]);
    }

    public function test_update_nameservers_requires_minimum_two()
    {
        $this->expectException(ValidationException::class);

        $nameservers = ['ns1.example.com'];

        $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);
    }

    public function test_update_nameservers_allows_maximum_four()
    {
        $this->expectException(ValidationException::class);

        $nameservers = [
            'ns1.example.com',
            'ns2.example.com',
            'ns3.example.com',
            'ns4.example.com',
            'ns5.example.com',
        ];

        $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);
    }

    public function test_update_nameservers_validates_hostname_format()
    {
        $this->expectException(ValidationException::class);

        $nameservers = [
            'ns1.example.com',
            'invalid..hostname',
        ];

        $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);
    }

    public function test_update_nameservers_rejects_duplicates()
    {
        $this->expectException(ValidationException::class);

        $nameservers = [
            'ns1.example.com',
            'ns1.example.com',
        ];

        $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);
    }

    public function test_update_nameservers_creates_audit_log()
    {
        $nameservers = [
            'ns1.example.com',
            'ns2.example.com',
        ];

        $this->service->updateNameservers($this->domain, $nameservers, $this->user->id);

        $this->assertDatabaseHas('audit_logs', [
            'partner_id' => $this->domain->partner_id,
            'user_id' => $this->user->id,
            'auditable_type' => Domain::class,
            'auditable_id' => $this->domain->id,
            'action' => 'nameservers_updated',
        ]);
    }

    public function test_get_nameservers_returns_ordered_list()
    {
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns2.example.com',
            'order' => 2,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.example.com',
            'order' => 1,
        ]);

        $nameservers = $this->service->getNameservers($this->domain);

        $this->assertEquals(['ns1.example.com', 'ns2.example.com'], $nameservers);
    }

    public function test_get_default_nameservers_from_partner()
    {
        $partner = $this->domain->partner;
        $partner->update([
            'settings' => [
                'default_nameservers' => [
                    'ns1.partner.com',
                    'ns2.partner.com',
                ],
            ],
        ]);

        $defaults = $this->service->getDefaultNameservers($this->domain);

        $this->assertEquals(['ns1.partner.com', 'ns2.partner.com'], $defaults);
    }

    public function test_get_default_nameservers_falls_back_to_registrar()
    {
        $defaults = $this->service->getDefaultNameservers($this->domain);

        $this->assertCount(2, $defaults);
        $this->assertStringContainsString('mock', $defaults[0]);
    }

    public function test_sync_nameservers_from_registrar()
    {
        // Mock registrar should return nameservers in getInfo
        $result = $this->service->syncNameservers($this->domain);

        // Mock registrar returns success with nameservers
        $this->assertTrue($result['success']);
    }

    public function test_update_nameservers_replaces_existing()
    {
        // Create initial nameservers
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'old1.example.com',
            'order' => 1,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'old2.example.com',
            'order' => 2,
        ]);

        // Update with new nameservers
        $newNameservers = [
            'new1.example.com',
            'new2.example.com',
        ];

        $this->service->updateNameservers($this->domain, $newNameservers, $this->user->id);

        // Old nameservers should be gone
        $this->assertDatabaseMissing('domain_nameservers', [
            'domain_id' => $this->domain->id,
            'nameserver' => 'old1.example.com',
        ]);

        // New nameservers should exist
        $this->assertDatabaseHas('domain_nameservers', [
            'domain_id' => $this->domain->id,
            'nameserver' => 'new1.example.com',
        ]);
    }
}
