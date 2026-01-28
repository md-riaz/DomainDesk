<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Registrar\RegistrarList;
use App\Models\Registrar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrarListTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_component_renders_successfully()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.registrar.registrar-list');
    }

    public function test_displays_registrars_list()
    {
        $registrar = Registrar::factory()->create([
            'name' => 'Test Registrar',
            'is_active' => true,
        ]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->assertSee('Test Registrar')
            ->assertSee('Active');
    }

    public function test_search_filters_registrars()
    {
        Registrar::factory()->create(['name' => 'ResellerClub']);
        Registrar::factory()->create(['name' => 'MockRegistrar']);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->set('search', 'Reseller')
            ->assertSee('ResellerClub')
            ->assertDontSee('MockRegistrar');
    }

    public function test_status_filter_works()
    {
        Registrar::factory()->create(['is_active' => true]);
        Registrar::factory()->create(['is_active' => false]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->set('statusFilter', 'active')
            ->assertSeeHtml('Active')
            ->set('statusFilter', 'inactive')
            ->assertSeeHtml('Inactive');
    }

    public function test_can_test_registrar_connection()
    {
        $registrar = Registrar::factory()->create();
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->call('testConnection', $registrar->id)
            ->assertDispatched('registrar-tested');
    }

    public function test_can_toggle_registrar_active_status()
    {
        $registrar = Registrar::factory()->create(['is_active' => true]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->call('toggleActive', $registrar->id)
            ->assertDispatched('registrar-updated');
        
        $this->assertFalse($registrar->fresh()->is_active);
    }

    public function test_can_set_default_registrar()
    {
        $registrar1 = Registrar::factory()->create(['is_active' => true, 'is_default' => true]);
        $registrar2 = Registrar::factory()->create(['is_active' => true, 'is_default' => false]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->call('setDefault', $registrar2->id)
            ->assertDispatched('registrar-updated');
        
        $this->assertFalse($registrar1->fresh()->is_default);
        $this->assertTrue($registrar2->fresh()->is_default);
    }

    public function test_cannot_set_inactive_registrar_as_default()
    {
        $registrar = Registrar::factory()->create(['is_active' => false]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->call('setDefault', $registrar->id)
            ->assertDispatched('registrar-error');
        
        $this->assertFalse($registrar->fresh()->is_default);
    }

    public function test_sorting_works()
    {
        Registrar::factory()->create(['name' => 'Zebra']);
        Registrar::factory()->create(['name' => 'Alpha']);
        
        $this->actingAs($this->superAdmin);
        
        $component = Livewire::test(RegistrarList::class)
            ->call('sortBy', 'name')
            ->assertSet('sortBy', 'name')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_pagination_works()
    {
        Registrar::factory()->count(20)->create();
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarList::class)
            ->assertViewHas('registrars', function ($registrars) {
                return $registrars->count() <= 15;
            });
    }

    public function test_health_status_is_cached()
    {
        $registrar = Registrar::factory()->create(['is_active' => true]);
        
        $this->actingAs($this->superAdmin);
        
        $component = Livewire::test(RegistrarList::class);
        
        $health1 = $component->call('getHealthStatus', $registrar->id);
        $health2 = $component->call('getHealthStatus', $registrar->id);
        
        $this->assertNotNull($health1);
    }

    public function test_unauthorized_user_cannot_access()
    {
        $partnerUser = User::factory()->partner()->create();
        
        $this->actingAs($partnerUser);
        
        Livewire::test(RegistrarList::class)
            ->assertForbidden();
    }

    public function test_guest_cannot_access()
    {
        Livewire::test(RegistrarList::class)
            ->assertForbidden();
    }
}
