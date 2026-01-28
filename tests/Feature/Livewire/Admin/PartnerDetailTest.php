<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\Role;
use App\Livewire\Admin\Partner\PartnerDetail;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => Role::SuperAdmin,
            'partner_id' => null,
        ]);

        $this->partner = Partner::factory()->create();
        Wallet::factory()->create(['partner_id' => $this->partner->id]);
    }

    public function test_super_admin_can_view_partner_detail(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->assertOk()
            ->assertSee($this->partner->name)
            ->assertSee($this->partner->email);
    }

    public function test_displays_partner_statistics(): void
    {
        // Create clients for partner
        User::factory(5)->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->assertOk()
            ->assertSee('Total Clients')
            ->assertSee('5');
    }

    public function test_displays_wallet_balance(): void
    {
        $this->partner->wallet->credit(1000.00, 'Test', createdBy: $this->superAdmin->id);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->assertOk()
            ->assertSee('Wallet Balance')
            ->assertSee('1,000.00');
    }

    public function test_displays_total_revenue(): void
    {
        Invoice::factory(3)->create([
            'partner_id' => $this->partner->id,
            'status' => 'paid',
            'total' => 150.00,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->assertOk()
            ->assertSee('Total Revenue')
            ->assertSee('450.00');
    }

    public function test_can_suspend_partner(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->call('suspendPartner')
            ->assertHasNoErrors();

        $this->partner->refresh();
        $this->assertEquals('suspended', $this->partner->status);
        $this->assertFalse($this->partner->is_active);
    }

    public function test_can_activate_partner(): void
    {
        $this->partner->update(['status' => 'suspended', 'is_active' => false]);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->call('activatePartner')
            ->assertHasNoErrors();

        $this->partner->refresh();
        $this->assertEquals('active', $this->partner->status);
        $this->assertTrue($this->partner->is_active);
    }

    public function test_can_impersonate_partner(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->call('impersonatePartner')
            ->assertRedirect(route('partner.dashboard'));

        $this->assertEquals($this->partner->id, session('impersonating_partner_id'));
    }

    public function test_can_open_adjust_wallet_modal(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerDetail::class, ['partnerId' => $this->partner->id])
            ->call('openAdjustWallet')
            ->assertSet('showAdjustWallet', true);
    }

    public function test_non_admin_cannot_view_partner_detail(): void
    {
        $user = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.partners.show', $this->partner->id))
            ->assertForbidden();
    }
}
