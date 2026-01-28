<?php

namespace Tests\Feature\Commands;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->client = User::factory()->create(['partner_id' => $this->partner->id]);
        
        $this->registrar = Registrar::factory()->create([
            'name' => 'Mock Registrar',
            'slug' => 'mock',
            'api_class' => 'App\\Services\\Registrar\\MockRegistrar',
            'is_active' => true,
        ]);
    }

    public function test_sync_domain_command_with_specific_domain(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null, // No registrar = will skip
        ]);

        $this->artisan('domain:sync', ['domain' => 'example.com'])
            ->assertExitCode(0);
    }

    public function test_sync_domain_command_fails_for_nonexistent_domain(): void
    {
        $this->artisan('domain:sync', ['domain' => 'nonexistent.com'])
            ->expectsOutput('Domain not found: nonexistent.com')
            ->assertExitCode(1);
    }

    public function test_sync_domain_command_with_force_flag(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
            'last_synced_at' => now(),
        ]);

        // Should skip because no registrar
        $this->artisan('domain:sync', [
            'domain' => 'example.com',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_sync_domain_command_syncs_all_domains(): void
    {
        // Create domains without registrar - will all be skipped
        Domain::factory()->count(3)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
            'status' => DomainStatus::Active,
            'last_synced_at' => null,
        ]);

        // Command should succeed even though all domains are skipped
        $this->artisan('domain:sync')
            ->assertExitCode(0);
    }

    public function test_sync_domain_command_with_limit(): void
    {
        // Create domains without registrar
        Domain::factory()->count(10)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
            'status' => DomainStatus::Active,
            'last_synced_at' => null,
        ]);

        $this->artisan('domain:sync', ['--limit' => 5])
            ->assertExitCode(0);
    }

    public function test_sync_domain_command_with_partner_filter(): void
    {
        $otherPartner = Partner::factory()->create();
        $otherClient = User::factory()->create(['partner_id' => $otherPartner->id]);

        Domain::factory()->create([
            'name' => 'partner1-domain.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
        ]);

        Domain::factory()->create([
            'name' => 'partner2-domain.com',
            'partner_id' => $otherPartner->id,
            'client_id' => $otherClient->id,
            'registrar_id' => null,
        ]);

        $this->artisan('domain:sync', ['--partner' => $this->partner->id])
            ->assertExitCode(0);
    }

    public function test_sync_domain_command_shows_no_domains_message(): void
    {
        $this->artisan('domain:sync')
            ->expectsOutput('No domains need syncing.')
            ->assertExitCode(0);
    }

    public function test_sync_status_command_with_defaults(): void
    {
        Domain::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

        // Will show "No domains to sync" because no registrar
        $this->artisan('domain:sync-status')
            ->assertExitCode(0);
    }

    public function test_sync_status_command_with_custom_days(): void
    {
        Domain::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(50),
        ]);

        $this->artisan('domain:sync-status', ['--days' => 60])
            ->assertExitCode(0);
    }

    public function test_sync_status_command_with_all_flag(): void
    {
        Domain::factory()->count(5)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
            'status' => DomainStatus::Active,
        ]);

        $this->artisan('domain:sync-status', ['--all' => true])
            ->assertExitCode(0);
    }

    public function test_sync_status_command_with_limit(): void
    {
        Domain::factory()->count(10)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => null,
            'status' => DomainStatus::Active,
        ]);

        $this->artisan('domain:sync-status', [
            '--all' => true,
            '--limit' => 3,
        ])->assertExitCode(0);
    }

    public function test_sync_status_command_shows_no_domains_message(): void
    {
        $this->artisan('domain:sync-status')
            ->expectsOutput('No domains to sync.')
            ->assertExitCode(0);
    }

    public function test_sync_tld_prices_command_with_specific_registrar(): void
    {
        Tld::factory()->create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'is_active' => true,
        ]);

        $this->markTestIncomplete('Requires mock registrar with getTldPricing method');
    }

    public function test_sync_tld_prices_command_fails_for_nonexistent_registrar(): void
    {
        $this->artisan('tld:sync-prices', ['registrar' => 'nonexistent'])
            ->expectsOutput('Registrar not found: nonexistent')
            ->assertExitCode(1);
    }

    public function test_sync_tld_prices_command_fails_for_inactive_registrar(): void
    {
        $inactiveRegistrar = Registrar::factory()->create([
            'slug' => 'inactive',
            'is_active' => false,
        ]);

        $this->artisan('tld:sync-prices', ['registrar' => 'inactive'])
            ->expectsOutput('Registrar is not active: inactive')
            ->assertExitCode(1);
    }

    public function test_sync_tld_prices_command_syncs_all_registrars(): void
    {
        Registrar::factory()->count(2)->create([
            'is_active' => true,
        ]);

        $this->markTestIncomplete('Requires mock registrar with getTldPricing method');
    }

    public function test_sync_tld_prices_command_shows_no_registrars_message(): void
    {
        Registrar::where('is_active', true)->delete();

        $this->artisan('tld:sync-prices')
            ->expectsOutput('No active registrars found.')
            ->assertExitCode(0);
    }

    public function test_sync_tld_prices_command_skips_registrar_with_no_tlds(): void
    {
        $registrar = Registrar::factory()->create([
            'slug' => 'empty',
            'is_active' => true,
        ]);

        $this->artisan('tld:sync-prices', ['registrar' => 'empty'])
            ->expectsOutput('No active TLDs found for this registrar.')
            ->assertExitCode(0);
    }
}
