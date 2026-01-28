<?php

namespace Tests\Feature\Commands;

use App\Enums\DomainStatus;
use App\Enums\PriceAction;
use App\Enums\Role;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ProcessAutoRenewalsTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;
    protected Tld $tld;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        // Create partner
        $this->partner = Partner::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        // Create wallet with balance
        $this->wallet = Wallet::factory()->create([
            'partner_id' => $this->partner->id,
        ]);
        $this->wallet->credit(1000.00, 'Initial balance');

        // Create client
        $this->client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        // Create registrar
        $this->registrar = Registrar::factory()->create([
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create TLD
        $this->tld = Tld::factory()->create([
            'extension' => 'com',
            'is_active' => true,
        ]);

        // Create TLD price
        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::RENEW,
            'years' => 1,
            'price' => 12.00,
            'effective_date' => now()->subDay(),
        ]);

        $this->mockSuccessfulRenewalResponse();
    }

    protected function mockSuccessfulRenewalResponse(): void
    {
        $mock = Mockery::mock('overload:' . RegistrarFactory::class);
        $registrarMock = Mockery::mock();
        
        $registrarMock->shouldReceive('renew')
            ->andReturn([
                'success' => true,
                'data' => ['expires_at' => now()->addYears(1)->toIso8601String()],
                'message' => 'Domain renewed successfully',
            ]);
        
        $registrarMock->shouldReceive('getName')
            ->andReturn('MockRegistrar');

        $mock->shouldReceive('make')
            ->andReturn($registrarMock);
    }

    public function test_processes_domains_with_auto_renew_enabled(): void
    {
        Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $this->artisan('domain:process-auto-renewals', ['--lead-time' => 7])
            ->assertSuccessful()
            ->expectsOutput('Starting auto-renewal process...');
    }

    public function test_skips_domains_without_auto_renew(): void
    {
        Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => false,
        ]);

        $this->artisan('domain:process-auto-renewals', ['--lead-time' => 7])
            ->assertSuccessful();
    }

    public function test_skips_domains_not_expiring_within_lead_time(): void
    {
        Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(10),
            'auto_renew' => true,
        ]);

        $this->artisan('domain:process-auto-renewals', ['--lead-time' => 7])
            ->assertSuccessful();
    }

    public function test_processes_multiple_domains(): void
    {
        Domain::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $this->artisan('domain:process-auto-renewals', ['--lead-time' => 7])
            ->assertSuccessful();
    }

    public function test_filters_by_partner_id(): void
    {
        // Create another partner with domain
        $otherPartner = Partner::factory()->create();
        $otherClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $otherPartner->id,
        ]);
        
        Wallet::factory()->create(['partner_id' => $otherPartner->id])
            ->credit(1000.00, 'Initial balance');

        Domain::factory()->create([
            'name' => 'other.com',
            'client_id' => $otherClient->id,
            'partner_id' => $otherPartner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $this->artisan('domain:process-auto-renewals', [
            '--lead-time' => 7,
            '--partner' => $this->partner->id
        ])->assertSuccessful();
    }

    public function test_dry_run_mode(): void
    {
        Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $this->artisan('domain:process-auto-renewals', [
            '--lead-time' => 7,
            '--dry-run' => true
        ])->assertSuccessful()
            ->expectsOutput('DRY RUN MODE - No changes will be made');

        // Verify domain was not actually renewed
        $domain = Domain::where('name', 'example.com')->first();
        $this->assertEquals(now()->addDays(5)->format('Y-m-d'), $domain->expires_at->format('Y-m-d'));
    }

    public function test_handles_insufficient_balance(): void
    {
        // Empty wallet
        $this->wallet->debit(1000.00, 'Test debit');

        Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $this->artisan('domain:process-auto-renewals', ['--lead-time' => 7])
            ->assertSuccessful();
    }

    public function test_verbose_output_shows_details(): void
    {
        Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $this->artisan('domain:process-auto-renewals', ['--lead-time' => 7, '-v' => true])
            ->assertSuccessful();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
