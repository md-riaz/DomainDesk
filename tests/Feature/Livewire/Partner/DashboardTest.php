<?php

namespace Tests\Feature\Livewire\Partner;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Livewire\Partner\Dashboard;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $partnerUser;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $this->wallet = Wallet::factory()->create(['partner_id' => $this->partner->id]);
        $this->wallet->credit(1000.00, 'Initial balance');
        
        $this->partnerUser = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $this->partner->id,
        ]);

        app(\App\Services\PartnerContextService::class)->setPartner($this->partner);
    }

    public function test_partner_can_view_dashboard()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(Dashboard::class)
            ->assertStatus(200)
            ->assertSee('Partner Dashboard');
    }

    public function test_non_partner_cannot_view_dashboard()
    {
        $client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($client);

        $this->get(route('partner.dashboard'))
            ->assertStatus(403);
    }

    public function test_dashboard_displays_correct_metrics()
    {
        $this->actingAs($this->partnerUser);

        $client1 = User::factory()->create(['role' => Role::Client, 'partner_id' => $this->partner->id]);
        $client2 = User::factory()->create(['role' => Role::Client, 'partner_id' => $this->partner->id]);

        Domain::factory()->count(3)->create([
            'partner_id' => $this->partner->id,
            'client_id' => $client1->id,
            'status' => DomainStatus::Active,
        ]);

        Domain::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $client2->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

        $invoice = Invoice::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $client1->id,
            'status' => InvoiceStatus::Paid,
            'total' => 150.00,
        ]);

        Livewire::test(Dashboard::class)
            ->assertSet('metrics.total_clients', 2)
            ->assertSet('metrics.total_domains', 4)
            ->assertSet('metrics.active_domains', 4)
            ->assertSet('metrics.expiring_soon', 1)
            ->assertSet('metrics.total_revenue', 150.00)
            ->assertSet('metrics.wallet_balance', 1000.00);
    }

    public function test_dashboard_shows_recent_activities()
    {
        $this->actingAs($this->partnerUser);

        AuditLog::factory()->count(15)->create([
            'partner_id' => $this->partner->id,
            'user_id' => $this->partnerUser->id,
        ]);

        Livewire::test(Dashboard::class)
            ->assertCount('recentActivities', 10);
    }

    public function test_refresh_metrics_clears_cache()
    {
        $this->actingAs($this->partnerUser);

        $component = Livewire::test(Dashboard::class);
        
        $initialMetrics = $component->get('metrics');

        User::factory()->create(['role' => Role::Client, 'partner_id' => $this->partner->id]);

        $component->call('refreshMetrics')
            ->assertDispatched('metrics-refreshed');
    }

    public function test_dashboard_isolates_partner_data()
    {
        $otherPartner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $otherClient = User::factory()->create(['role' => Role::Client, 'partner_id' => $otherPartner->id]);
        
        Domain::factory()->create([
            'partner_id' => $otherPartner->id,
            'client_id' => $otherClient->id,
            'name' => 'other-domain-' . uniqid() . '.com',
        ]);

        $this->actingAs($this->partnerUser);

        Livewire::test(Dashboard::class)
            ->assertSet('metrics.total_clients', 0)
            ->assertSet('metrics.total_domains', 0);
    }

    public function test_dashboard_handles_empty_state()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(Dashboard::class)
            ->assertSet('metrics.total_clients', 0)
            ->assertSet('metrics.total_domains', 0)
            ->assertSet('metrics.active_domains', 0)
            ->assertSet('metrics.expiring_soon', 0)
            ->assertSet('metrics.total_revenue', 0);
    }

    public function test_metrics_are_cached()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(Dashboard::class);

        $this->assertTrue(
            \Illuminate\Support\Facades\Cache::has('partner.dashboard.metrics.' . $this->partner->id)
        );
    }
}
