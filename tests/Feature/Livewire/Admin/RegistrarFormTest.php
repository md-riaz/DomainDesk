<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Registrar\RegistrarForm;
use App\Models\Registrar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrarFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_component_renders_for_new_registrar()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.registrar.registrar-form');
    }

    public function test_component_renders_for_editing()
    {
        $registrar = Registrar::factory()->create();
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class, ['registrarId' => $registrar->id])
            ->assertStatus(200)
            ->assertSet('name', $registrar->name)
            ->assertSet('api_class', $registrar->api_class);
    }

    public function test_can_create_new_registrar()
    {
        $this->actingAs($this->superAdmin);
        
        $credentials = ['api_key' => 'test-key', 'user_id' => '12345'];
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'New Registrar')
            ->set('api_class', 'App\\Services\\Registrar\\MockRegistrar')
            ->set('credentialsJson', json_encode($credentials))
            ->set('is_active', true)
            ->set('is_default', false)
            ->call('save')
            ->assertRedirect(route('admin.registrars.list'));
        
        $this->assertDatabaseHas('registrars', [
            'name' => 'New Registrar',
            'api_class' => 'App\\Services\\Registrar\\MockRegistrar',
            'is_active' => true,
        ]);
        
        $registrar = Registrar::where('name', 'New Registrar')->first();
        $this->assertEquals($credentials, $registrar->credentials);
    }

    public function test_can_update_existing_registrar()
    {
        $registrar = Registrar::factory()->create([
            'name' => 'Old Name',
            'is_active' => false,
        ]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class, ['registrarId' => $registrar->id])
            ->set('name', 'Updated Name')
            ->set('is_active', true)
            ->call('save')
            ->assertRedirect(route('admin.registrars.list'));
        
        $this->assertDatabaseHas('registrars', [
            'id' => $registrar->id,
            'name' => 'Updated Name',
            'is_active' => true,
        ]);
    }

    public function test_credentials_are_encrypted()
    {
        $this->actingAs($this->superAdmin);
        
        $credentials = ['api_key' => 'secret-key-123', 'user_id' => 'user-456'];
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'Secure Registrar')
            ->set('api_class', 'App\\Services\\Registrar\\MockRegistrar')
            ->set('credentialsJson', json_encode($credentials))
            ->call('save');
        
        $registrar = Registrar::where('name', 'Secure Registrar')->first();
        
        $this->assertEquals($credentials, $registrar->credentials);
        
        $rawValue = $registrar->getAttributes()['credentials'];
        $this->assertNotEquals(json_encode($credentials), $rawValue);
    }

    public function test_validation_requires_name()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->set('name', '')
            ->set('api_class', 'App\\Services\\Registrar\\MockRegistrar')
            ->set('credentialsJson', '{}')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_validation_requires_unique_name()
    {
        $existing = Registrar::factory()->create(['name' => 'Existing']);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'Existing')
            ->set('api_class', 'App\\Services\\Registrar\\MockRegistrar')
            ->set('credentialsJson', '{}')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_validation_requires_api_class()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'Test')
            ->set('api_class', '')
            ->set('credentialsJson', '{}')
            ->call('save')
            ->assertHasErrors(['api_class' => 'required']);
    }

    public function test_validation_requires_valid_json_credentials()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'Test')
            ->set('api_class', 'App\\Services\\Registrar\\MockRegistrar')
            ->set('credentialsJson', 'invalid json')
            ->call('save')
            ->assertHasErrors(['credentialsJson' => 'json']);
    }

    public function test_can_test_connection_before_saving()
    {
        $this->actingAs($this->superAdmin);
        
        $credentials = ['api_key' => 'test-key'];
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'Test Registrar')
            ->set('api_class', 'App\\Services\\Registrar\\MockRegistrar')
            ->set('credentialsJson', json_encode($credentials))
            ->call('testConnection')
            ->assertSet('isTesting', false)
            ->assertSet('testResult', 'success');
    }

    public function test_test_connection_fails_for_invalid_class()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'Test')
            ->set('api_class', 'NonExistentClass')
            ->set('credentialsJson', '{}')
            ->call('testConnection')
            ->assertSet('testResult', 'error');
    }

    public function test_setting_as_default_removes_other_defaults()
    {
        $existing = Registrar::factory()->create(['is_default' => true]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->set('name', 'New Default')
            ->set('api_class', 'App\\Services\\Registrar\\MockRegistrar')
            ->set('credentialsJson', '{}')
            ->set('is_default', true)
            ->call('save');
        
        $this->assertFalse($existing->fresh()->is_default);
        $this->assertTrue(Registrar::where('name', 'New Default')->first()->is_default);
    }

    public function test_available_classes_are_populated()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(RegistrarForm::class)
            ->assertSet('availableClasses', function ($classes) {
                return is_array($classes) && count($classes) > 0;
            });
    }

    public function test_unauthorized_user_cannot_access()
    {
        $partnerUser = User::factory()->partner()->create();
        
        $this->actingAs($partnerUser);
        
        Livewire::test(RegistrarForm::class)
            ->assertForbidden();
    }

    public function test_guest_cannot_access()
    {
        Livewire::test(RegistrarForm::class)
            ->assertForbidden();
    }
}
