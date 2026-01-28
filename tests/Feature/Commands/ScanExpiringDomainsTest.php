<?php

namespace Tests\Feature\Commands;

use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\DomainExpiryAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScanExpiringDomainsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_scans_domains_expiring_in_30_days(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertSentTo($client, DomainExpiryAlert::class);
    }

    public function test_scans_domains_expiring_in_15_days(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDays(15),
            'status' => 'active',
        ]);

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertSentTo($client, DomainExpiryAlert::class);
    }

    public function test_scans_domains_expiring_in_7_days(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertSentTo($client, DomainExpiryAlert::class);
    }

    public function test_scans_domains_expiring_in_1_day(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertSentTo($client, DomainExpiryAlert::class);
    }

    public function test_ignores_domains_not_expiring_soon(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDays(60),
            'status' => 'active',
        ]);

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_ignores_inactive_domains(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDays(30),
            'status' => 'expired',
        ]);

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_handles_multiple_domains_for_same_client(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        Domain::factory()->count(3)->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertSentTo($client, DomainExpiryAlert::class, 3);
    }

    public function test_handles_domains_without_client(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        // Delete the client to simulate missing client
        $client->forceDelete();

        $this->artisan('domains:scan-expiring')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
