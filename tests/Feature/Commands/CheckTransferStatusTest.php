<?php

namespace Tests\Feature\Commands;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckTransferStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->registrar = Registrar::factory()->create([
            'name' => 'Mock Registrar',
            'api_class' => 'App\Services\Registrar\MockRegistrar',
            'is_active' => true,
        ]);
        $this->client = User::factory()->create([
            'partner_id' => $this->partner->id,
            'role' => 'client',
        ]);
    }

    /** @test */
    public function it_checks_domains_in_transfer_state(): void
    {
        Domain::factory()->create([
            'name' => 'example1.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        Domain::factory()->create([
            'name' => 'example2.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::TransferInProgress,
            'transfer_initiated_at' => now()->subDays(3),
        ]);

        // Should not check active domains
        Domain::factory()->create([
            'name' => 'active.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
        ]);

        $this->artisan('domains:check-transfer-status')
            ->expectsOutput('Found 2 domain(s) to check.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_updates_transfer_status_to_completed(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(8), // Should be completed
        ]);

        $this->artisan('domains:check-transfer-status')
            ->assertExitCode(0);

        $domain->refresh();
        $this->assertEquals(DomainStatus::TransferCompleted, $domain->status);
    }

    /** @test */
    public function it_can_check_specific_domain(): void
    {
        Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        Domain::factory()->create([
            'name' => 'other.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        $this->artisan('domains:check-transfer-status', ['--domain' => 'example.com'])
            ->expectsOutput('Found 1 domain(s) to check.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_respects_limit_option(): void
    {
        // Create 5 domains
        for ($i = 1; $i <= 5; $i++) {
            Domain::factory()->create([
                'name' => "example{$i}.com",
                'partner_id' => $this->partner->id,
                'client_id' => $this->client->id,
                'registrar_id' => $this->registrar->id,
                'status' => DomainStatus::PendingTransfer,
                'transfer_initiated_at' => now()->subDays($i),
            ]);
        }

        $this->artisan('domains:check-transfer-status', ['--limit' => 3])
            ->expectsOutput('Found 3 domain(s) to check.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_no_transferring_domains_gracefully(): void
    {
        $this->artisan('domains:check-transfer-status')
            ->expectsOutput('No domains found in transferring state.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_displays_summary_table(): void
    {
        Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        $this->artisan('domains:check-transfer-status')
            ->expectsOutput('Transfer status check complete!')
            ->expectsTable(['Metric', 'Count'], [
                ['Domains Checked', 1],
                ['Status Updated', 0],
                ['Failed/Errors', 0],
            ])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_checks_oldest_transfers_first(): void
    {
        $oldest = Domain::factory()->create([
            'name' => 'oldest.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(5),
        ]);

        $newest = Domain::factory()->create([
            'name' => 'newest.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        $this->artisan('domains:check-transfer-status')
            ->expectsOutputToContain('oldest.com')
            ->assertExitCode(0);
    }
}
