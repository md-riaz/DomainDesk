<?php

namespace Tests\Feature\Livewire\Partner;

use App\Enums\Role;
use App\Livewire\Partner\Client\AddClient;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AddClientTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $partnerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $this->partnerUser = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $this->partner->id,
        ]);

        app(\App\Services\PartnerContextService::class)->setPartner($this->partner);
    }

    public function test_partner_can_view_add_client_form()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->assertStatus(200)
            ->assertSee('Add New Client');
    }

    public function test_partner_can_create_client()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'TestPass123')
            ->call('save')
            ->assertSessionHas('success')
            ->assertDispatched('client-created');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => Role::Client->value,
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_created_client_password_is_hashed()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'TestPass123')
            ->call('save');

        $client = User::where('email', 'john@example.com')->first();
        
        $this->assertTrue(Hash::check('TestPass123', $client->password));
    }

    public function test_name_is_required()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', '')
            ->set('email', 'john@example.com')
            ->set('password', 'TestPass123')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_email_is_required()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', '')
            ->set('password', 'TestPass123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_email_must_be_valid()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'invalid-email')
            ->set('password', 'TestPass123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_email_must_be_unique()
    {
        $this->actingAs($this->partnerUser);

        User::factory()->create([
            'email' => 'existing@example.com',
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'existing@example.com')
            ->set('password', 'TestPass123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_password_is_required()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', '')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_password_must_be_minimum_8_characters()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'short')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_generate_password_creates_new_password()
    {
        $this->actingAs($this->partnerUser);

        $component = Livewire::test(AddClient::class);
        $initialPassword = $component->get('password');

        $component->call('generatePassword');
        $newPassword = $component->get('password');

        $this->assertNotEquals($initialPassword, $newPassword);
        $this->assertTrue(strlen($newPassword) >= 12);
    }

    public function test_success_message_shows_credentials()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'TestPass123')
            ->call('save')
            ->assertSet('showSuccess', true)
            ->assertSet('generatedPassword', 'TestPass123')
            ->assertSee('john@example.com')
            ->assertSee('TestPass123');
    }

    public function test_reset_form_clears_all_fields()
    {
        $this->actingAs($this->partnerUser);

        $component = Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->call('resetForm');

        $component->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('showSuccess', false);
    }

    public function test_client_is_auto_assigned_to_current_partner()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'TestPass123')
            ->call('save');

        $client = User::where('email', 'john@example.com')->first();
        
        $this->assertEquals($this->partner->id, $client->partner_id);
    }

    public function test_client_role_is_auto_assigned()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(AddClient::class)
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'TestPass123')
            ->call('save');

        $client = User::where('email', 'john@example.com')->first();
        
        $this->assertEquals(Role::Client, $client->role);
    }

    public function test_non_partner_cannot_add_client()
    {
        $client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($client);

        $this->get(route('partner.clients.add'))
            ->assertStatus(403);
    }
}
