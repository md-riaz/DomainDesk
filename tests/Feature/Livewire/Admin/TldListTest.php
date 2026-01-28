<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Tld\TldList;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TldListTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Registrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->registrar = Registrar::factory()->create();
    }

    public function test_component_renders_successfully()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.tld.tld-list');
    }

    public function test_displays_tlds_list()
    {
        $tld = Tld::factory()->create([
            'extension' => 'com',
            'registrar_id' => $this->registrar->id,
        ]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->assertSee('.com')
            ->assertSee($this->registrar->name);
    }

    public function test_search_filters_tlds()
    {
        Tld::factory()->create(['extension' => 'com']);
        Tld::factory()->create(['extension' => 'net']);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('search', 'com')
            ->assertSee('.com')
            ->assertDontSee('.net');
    }

    public function test_registrar_filter_works()
    {
        $registrar2 = Registrar::factory()->create();
        
        $tld1 = Tld::factory()->create(['registrar_id' => $this->registrar->id, 'extension' => 'com']);
        $tld2 = Tld::factory()->create(['registrar_id' => $registrar2->id, 'extension' => 'net']);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('registrarFilter', $this->registrar->id)
            ->assertSee('.com')
            ->assertDontSee('.net');
    }

    public function test_status_filter_works()
    {
        Tld::factory()->create(['extension' => 'com', 'is_active' => true]);
        Tld::factory()->create(['extension' => 'net', 'is_active' => false]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('statusFilter', 'active')
            ->assertSee('.com')
            ->assertDontSee('.net');
    }

    public function test_feature_filter_works()
    {
        Tld::factory()->create(['extension' => 'com', 'supports_dns' => true]);
        Tld::factory()->create(['extension' => 'net', 'supports_dns' => false]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('featureFilter', 'dns')
            ->assertSee('.com')
            ->assertDontSee('.net');
    }

    public function test_can_toggle_tld_active_status()
    {
        $tld = Tld::factory()->create(['is_active' => true]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->call('toggleActive', $tld->id)
            ->assertDispatched('tld-updated');
        
        $this->assertFalse($tld->fresh()->is_active);
    }

    public function test_can_bulk_activate_tlds()
    {
        $tld1 = Tld::factory()->create(['is_active' => false]);
        $tld2 = Tld::factory()->create(['is_active' => false]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('selectedTlds', [$tld1->id, $tld2->id])
            ->call('bulkActivate');
        
        $this->assertTrue($tld1->fresh()->is_active);
        $this->assertTrue($tld2->fresh()->is_active);
    }

    public function test_can_bulk_deactivate_tlds()
    {
        $tld1 = Tld::factory()->create(['is_active' => true]);
        $tld2 = Tld::factory()->create(['is_active' => true]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('selectedTlds', [$tld1->id, $tld2->id])
            ->call('bulkDeactivate');
        
        $this->assertFalse($tld1->fresh()->is_active);
        $this->assertFalse($tld2->fresh()->is_active);
    }

    public function test_can_bulk_assign_registrar()
    {
        $newRegistrar = Registrar::factory()->create();
        $tld1 = Tld::factory()->create(['registrar_id' => $this->registrar->id]);
        $tld2 = Tld::factory()->create(['registrar_id' => $this->registrar->id]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('selectedTlds', [$tld1->id, $tld2->id])
            ->call('bulkAssignRegistrar', $newRegistrar->id)
            ->assertDispatched('tld-updated');
        
        $this->assertEquals($newRegistrar->id, $tld1->fresh()->registrar_id);
        $this->assertEquals($newRegistrar->id, $tld2->fresh()->registrar_id);
    }

    public function test_bulk_action_requires_selection()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('selectedTlds', [])
            ->call('bulkActivate')
            ->assertDispatched('tld-error');
    }

    public function test_select_all_functionality()
    {
        $tlds = Tld::factory()->count(3)->create();
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('selectAll', true)
            ->call('toggleSelectAll')
            ->assertSet('selectedTlds', function ($selected) use ($tlds) {
                return count($selected) === $tlds->count();
            });
    }

    public function test_displays_base_price()
    {
        $tld = Tld::factory()->create();
        
        TldPrice::factory()->create([
            'tld_id' => $tld->id,
            'action' => 'register',
            'years' => 1,
            'price' => 12.99,
            'effective_date' => now()->subDay(),
        ]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->assertSee('12.99');
    }

    public function test_displays_features_badges()
    {
        $tld = Tld::factory()->create([
            'supports_dns' => true,
            'supports_whois_privacy' => true,
        ]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->assertSee('DNS')
            ->assertSee('Privacy');
    }

    public function test_sorting_works()
    {
        Tld::factory()->create(['extension' => 'zzz']);
        Tld::factory()->create(['extension' => 'aaa']);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->set('sortBy', 'extension')
            ->set('sortDirection', 'asc')
            ->assertSet('sortBy', 'extension')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_pagination_works()
    {
        Tld::factory()->count(60)->create();
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldList::class)
            ->assertViewHas('tlds', function ($tlds) {
                return $tlds->count() === 50;
            });
    }

    public function test_unauthorized_user_cannot_access()
    {
        $partnerUser = User::factory()->partner()->create();
        
        $this->actingAs($partnerUser);
        
        $response = $this->get(route('admin.tlds.list'));
        
        $response->assertForbidden();
    }

    public function test_guest_cannot_access()
    {
        $response = $this->get(route('admin.tlds.list'));
        
        $response->assertRedirect(route('login'));
    }
}
