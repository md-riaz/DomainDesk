<?php

namespace Tests\Feature\Services;

use App\Enums\DomainStatus;
use App\Enums\PriceAction;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\DomainContact;
use App\Models\DomainNameserver;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use App\Services\RegistrarSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RegistrarSyncService $syncService;
    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;
    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncService = app(RegistrarSyncService::class);

        $this->partner = Partner::factory()->create();
        $this->client = User::factory()->create(['partner_id' => $this->partner->id]);
        
        $this->registrar = Registrar::factory()->create([
            'name' => 'Mock Registrar',
            'slug' => 'mock',
            'api_class' => 'App\\Services\\Registrar\\MockRegistrar',
            'is_active' => true,
        ]);

        $this->domain = Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addYear(),
        ]);
    }

    public function test_sync_domain_updates_status(): void
    {
        $this->markTestIncomplete('Requires mock registrar configuration');
    }

    public function test_sync_domain_updates_expiry_date(): void
    {
        $this->markTestIncomplete('Requires mock registrar configuration');
    }

    public function test_sync_domain_updates_nameservers(): void
    {
        // Create existing nameservers
        DomainNameserver::factory()->create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.old.com',
            'order' => 1,
        ]);

        DomainNameserver::factory()->create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns2.old.com',
            'order' => 2,
        ]);

        $this->assertCount(2, $this->domain->fresh()->nameservers);

        $this->markTestIncomplete('Requires mock registrar with getInfo method returning nameservers');
    }

    public function test_sync_domain_skips_recently_synced(): void
    {
        $this->domain->update(['last_synced_at' => now()->subHours(3)]);

        $result = $this->syncService->syncDomain($this->domain);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['skipped']);
        $this->assertEquals('Recently synced', $result['reason']);
    }

    public function test_sync_domain_with_force_ignores_recent_sync(): void
    {
        $this->domain->update(['last_synced_at' => now()->subHours(3)]);

        $this->markTestIncomplete('Requires mock registrar configuration');
    }

    public function test_sync_domain_skips_if_no_registrar(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'no-registrar.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => null,
        ]);

        $result = $this->syncService->syncDomain($domain);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['skipped']);
        $this->assertEquals('No registrar assigned', $result['reason']);
    }

    public function test_sync_domain_creates_audit_log_for_changes(): void
    {
        $this->markTestIncomplete('Requires mock registrar configuration to test actual changes');
        
        // The test would verify:
        // 1. Sync domain with changes
        // 2. Check AuditLog entries were created
        // 3. Verify audit log contains correct old/new values
    }

    public function test_sync_domains_batch_processes_multiple(): void
    {
        $domains = Domain::factory()->count(5)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
        ]);

        $callbackCalled = 0;
        $result = $this->syncService->syncDomains($domains, false, function () use (&$callbackCalled) {
            $callbackCalled++;
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertEquals(5, $result['stats']['total']);
        $this->assertEquals(5, $callbackCalled);
    }

    public function test_sync_domain_status_updates_only_status(): void
    {
        $this->markTestIncomplete('Requires mock registrar configuration');
    }

    public function test_sync_domain_status_logs_status_change(): void
    {
        $this->markTestIncomplete('Requires mock registrar configuration');
    }

    public function test_sync_tld_prices_creates_new_prices(): void
    {
        $tld = Tld::factory()->create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'min_years' => 1,
            'max_years' => 1,
            'is_active' => true,
        ]);

        $this->markTestIncomplete('Requires mock registrar with getTldPricing method');
    }

    public function test_sync_tld_prices_updates_changed_prices(): void
    {
        $tld = Tld::factory()->create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'min_years' => 1,
            'max_years' => 1,
            'is_active' => true,
        ]);

        // Create existing price
        TldPrice::factory()->create([
            'tld_id' => $tld->id,
            'action' => PriceAction::REGISTER->value,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subMonth(),
        ]);

        $this->markTestIncomplete('Requires mock registrar with getTldPricing method returning different price');
    }

    public function test_sync_tld_prices_logs_significant_changes(): void
    {
        $this->markTestIncomplete('Test would verify that price changes > 10% are logged as warnings');
    }

    public function test_get_domains_needing_sync_prioritizes_expiring(): void
    {
        // Create domains with different expiry dates
        $expiringSoon = Domain::factory()->create([
            'name' => 'expiring-soon.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'expires_at' => now()->addDays(15),
            'status' => DomainStatus::Active,
            'last_synced_at' => now()->subDays(2),
        ]);

        $expiringLater = Domain::factory()->create([
            'name' => 'expiring-later.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'expires_at' => now()->addDays(90),
            'status' => DomainStatus::Active,
            'last_synced_at' => now()->subDays(2),
        ]);

        $domains = $this->syncService->getDomainsNeedingSync(10);

        $this->assertGreaterThan(0, $domains->count());
        $this->assertEquals('expiring-soon.com', $domains->first()->name);
    }

    public function test_get_domains_needing_sync_respects_limit(): void
    {
        Domain::factory()->count(20)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'last_synced_at' => null,
        ]);

        $domains = $this->syncService->getDomainsNeedingSync(5);

        $this->assertEquals(5, $domains->count());
    }

    public function test_get_expiring_domains_returns_correct_domains(): void
    {
        $expiringSoon = Domain::factory()->create([
            'name' => 'expiring.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'expires_at' => now()->addDays(15),
            'status' => DomainStatus::Active,
        ]);

        $notExpiring = Domain::factory()->create([
            'name' => 'safe.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'expires_at' => now()->addDays(90),
            'status' => DomainStatus::Active,
        ]);

        $domains = $this->syncService->getExpiringDomains(30);

        $this->assertCount(1, $domains);
        $this->assertEquals('expiring.com', $domains->first()->name);
    }

    public function test_domain_needs_sync_returns_true_if_never_synced(): void
    {
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'last_synced_at' => null,
        ]);

        $this->assertTrue($domain->needsSync());
    }

    public function test_domain_needs_sync_returns_true_if_old_sync(): void
    {
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'last_synced_at' => now()->subHours(10),
        ]);

        $this->assertTrue($domain->needsSync(6));
    }

    public function test_domain_needs_sync_returns_false_if_recently_synced(): void
    {
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'last_synced_at' => now()->subHours(3),
        ]);

        $this->assertFalse($domain->needsSync(6));
    }

    public function test_mark_as_synced_updates_timestamp_and_metadata(): void
    {
        $this->assertNull($this->domain->last_synced_at);

        $metadata = ['test' => 'value', 'changes' => 2];
        $this->domain->markAsSynced($metadata);

        $this->domain->refresh();
        $this->assertNotNull($this->domain->last_synced_at);
        $this->assertEquals($metadata, $this->domain->sync_metadata);
    }

    public function test_sync_service_tracks_statistics(): void
    {
        $domains = Domain::factory()->count(3)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
        ]);

        $this->syncService->syncDomains($domains);

        $stats = $this->syncService->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('synced', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('skipped', $stats);
        $this->assertArrayHasKey('changes', $stats);
        $this->assertEquals(3, $stats['total']);
    }

    public function test_map_registrar_status_correctly(): void
    {
        $reflection = new \ReflectionClass($this->syncService);
        $method = $reflection->getMethod('mapRegistrarStatus');
        $method->setAccessible(true);

        $this->assertEquals(DomainStatus::Active, $method->invoke($this->syncService, 'active'));
        $this->assertEquals(DomainStatus::Active, $method->invoke($this->syncService, 'ok'));
        $this->assertEquals(DomainStatus::Expired, $method->invoke($this->syncService, 'expired'));
        $this->assertEquals(DomainStatus::GracePeriod, $method->invoke($this->syncService, 'grace'));
        $this->assertEquals(DomainStatus::Redemption, $method->invoke($this->syncService, 'redemption'));
        $this->assertEquals(DomainStatus::Suspended, $method->invoke($this->syncService, 'suspended'));
        $this->assertEquals(DomainStatus::TransferredOut, $method->invoke($this->syncService, 'transferred_out'));
    }
}
