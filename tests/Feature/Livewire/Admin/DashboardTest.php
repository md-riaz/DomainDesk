<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\Role;
use App\Livewire\Admin\Dashboard;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        // Create super admin
        $this->superAdmin = User::factory()->create([
            'role' => Role::SuperAdmin,
            'partner_id' => null,
        ]);

        // Create test partner
        $this->partner = Partner::factory()->create([
            'status' => 'active',
            'is_active' => true,
        ]);

        // Create wallet for partner
        Wallet::factory()->create(['partner_id' => $this->partner->id]);
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSee('Admin Dashboard');
    }

    public function test_non_super_admin_cannot_access_dashboard(): void
    {
        $partner = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($partner)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_displays_partner_metrics(): void
    {
        // Create additional partners
        Partner::factory(5)->create(['status' => 'active']);
        Partner::factory(2)->create(['status' => 'suspended']);

        $this->actingAs($this->superAdmin);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSee('Total Partners')
            ->assertViewHas('metrics', function ($metrics) {
                return $metrics['partners']['total'] === 8 &&
                       $metrics['partners']['active'] === 6 &&
                       $metrics['partners']['suspended'] === 2;
            });
    }

    public function test_dashboard_displays_revenue_metrics(): void
    {
        // Create paid invoices
        Invoice::factory(5)->create([
            'partner_id' => $this->partner->id,
            'status' => 'paid',
            'total' => 100.00,
            'paid_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSee('Total Revenue')
            ->assertViewHas('metrics', function ($metrics) {
                return $metrics['revenue']['total'] == 500.00;
            });
    }

    public function test_dashboard_can_refresh_metrics(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(Dashboard::class)
            ->call('refreshMetrics')
            ->assertDispatched('metrics-refreshed');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }
}
