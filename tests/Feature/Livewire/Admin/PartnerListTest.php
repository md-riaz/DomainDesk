<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\Role;
use App\Livewire\Admin\Partner\PartnerList;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerListTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => Role::SuperAdmin,
            'partner_id' => null,
        ]);
    }

    public function test_super_admin_can_view_partner_list(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerList::class)
            ->assertOk()
            ->assertSee('Partner Management');
    }

    public function test_partner_list_displays_all_partners(): void
    {
        $partners = Partner::factory(5)->create();
        
        foreach ($partners as $partner) {
            Wallet::factory()->create(['partner_id' => $partner->id]);
        }

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerList::class)
            ->assertOk()
            ->assertSee($partners[0]->name)
            ->assertSee($partners[1]->email);
    }

    public function test_can_search_partners_by_name(): void
    {
        $partner1 = Partner::factory()->create(['name' => 'ACME Corporation']);
        $partner2 = Partner::factory()->create(['name' => 'XYZ Company']);
        
        Wallet::factory()->create(['partner_id' => $partner1->id]);
        Wallet::factory()->create(['partner_id' => $partner2->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerList::class)
            ->set('search', 'ACME')
            ->assertSee('ACME Corporation')
            ->assertDontSee('XYZ Company');
    }

    public function test_can_filter_partners_by_status(): void
    {
        $activePartner = Partner::factory()->create(['status' => 'active']);
        $suspendedPartner = Partner::factory()->create(['status' => 'suspended']);
        
        Wallet::factory()->create(['partner_id' => $activePartner->id]);
        Wallet::factory()->create(['partner_id' => $suspendedPartner->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerList::class)
            ->set('statusFilter', 'active')
            ->assertSee($activePartner->name)
            ->assertDontSee($suspendedPartner->name);
    }

    public function test_can_sort_partners(): void
    {
        Partner::factory()->create(['name' => 'Zebra Corp']);
        Partner::factory()->create(['name' => 'Alpha Inc']);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerList::class)
            ->set('sortBy', 'name')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['Alpha Inc', 'Zebra Corp']);
    }

    public function test_can_impersonate_partner(): void
    {
        $partner = Partner::factory()->create();
        Wallet::factory()->create(['partner_id' => $partner->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerList::class)
            ->call('impersonatePartner', $partner->id)
            ->assertRedirect(route('partner.dashboard'));

        $this->assertEquals($partner->id, session('impersonating_partner_id'));
    }

    public function test_export_csv_returns_downloadable_file(): void
    {
        Partner::factory(3)->create();

        $this->actingAs($this->superAdmin);

        $response = Livewire::test(PartnerList::class)
            ->call('exportCsv');

        $this->assertNotNull($response);
    }

    public function test_pagination_works(): void
    {
        Partner::factory(25)->create();

        $this->actingAs($this->superAdmin);

        Livewire::test(PartnerList::class)
            ->assertOk()
            ->assertViewHas('partners', function ($partners) {
                return $partners->count() === 20; // Default per page
            });
    }

    public function test_non_admin_cannot_access_partner_list(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $partner->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.partners.list'))
            ->assertForbidden();
    }
}
