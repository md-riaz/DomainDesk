<?php

namespace Tests\Feature\Livewire;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Livewire\Client\Dashboard;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Partner $partner;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->client = User::factory()->client()->create(['partner_id' => $this->partner->id]);
    }

    public function test_dashboard_renders_successfully()
    {
        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSuccessful()
            ->assertSee('Dashboard');
    }

    public function test_dashboard_shows_correct_metrics()
    {
        // Create domains with different statuses
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(60),
        ]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(15), // Expiring soon
        ]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Expired,
        ]);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSet('metrics.total_domains', 3)
            ->assertSet('metrics.active_domains', 2)
            ->assertSet('metrics.expiring_soon', 1);
    }

    public function test_dashboard_shows_recent_domains()
    {
        $domain = Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'example.com',
        ]);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSee('example.com');
    }

    public function test_dashboard_shows_recent_invoices()
    {
        $invoice = Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-123',
        ]);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSee('INV-123');
    }

    public function test_dashboard_caches_metrics()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create();

        $cacheKey = 'dashboard.metrics.' . $this->client->id;
        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, 300, \Closure::class)
            ->andReturn([
                'total_domains' => 1,
                'active_domains' => 1,
                'expiring_soon' => 0,
                'pending_renewals' => 0,
            ]);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class);
    }

    public function test_dashboard_refresh_metrics_clears_cache()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('dashboard.metrics.' . $this->client->id);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->call('refreshMetrics');
    }

    public function test_dashboard_only_shows_client_own_data()
    {
        $otherClient = User::factory()->client()->create(['partner_id' => $this->partner->id]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'my-domain.com',
        ]);
        
        Domain::factory()->for($this->partner)->for($otherClient, 'client')->create([
            'name' => 'other-domain.com',
        ]);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSee('my-domain.com')
            ->assertDontSee('other-domain.com');
    }

    public function test_dashboard_shows_empty_state_for_no_domains()
    {
        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSee('No domains yet');
    }

    public function test_dashboard_shows_empty_state_for_no_invoices()
    {
        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSee('No invoices yet');
    }

    public function test_dashboard_calculates_pending_renewals_correctly()
    {
        // Active domain expiring soon without auto-renew
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(15),
            'auto_renew' => false,
        ]);
        
        // Active domain expiring soon with auto-renew (should not count)
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(20),
            'auto_renew' => true,
        ]);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSet('metrics.pending_renewals', 1);
    }

    public function test_dashboard_limits_recent_domains_to_five()
    {
        Domain::factory()->count(10)->for($this->partner)->for($this->client, 'client')->create();

        $component = Livewire::actingAs($this->client)
            ->test(Dashboard::class);

        $this->assertCount(5, $component->get('recentDomains'));
    }

    public function test_dashboard_limits_recent_invoices_to_five()
    {
        Invoice::factory()->count(10)->for($this->partner)->for($this->client, 'client')->create();

        $component = Livewire::actingAs($this->client)
            ->test(Dashboard::class);

        $this->assertCount(5, $component->get('recentInvoices'));
    }

    public function test_dashboard_displays_status_badges_correctly()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
        ]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Expired,
        ]);

        Livewire::actingAs($this->client)
            ->test(Dashboard::class)
            ->assertSee('Active')
            ->assertSee('Expired');
    }

    public function test_unauthenticated_user_cannot_access_dashboard()
    {
        $this->get(route('client.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_partner_user_cannot_access_client_dashboard()
    {
        $partner = User::factory()->partner()->create(['partner_id' => $this->partner->id]);

        $this->actingAs($partner)
            ->get(route('client.dashboard'))
            ->assertForbidden();
    }
}
