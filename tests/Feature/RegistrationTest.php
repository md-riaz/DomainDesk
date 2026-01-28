<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Auth\Register;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default partner for testing
        Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'slug' => 'test-partner',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function new_users_can_register(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('client.dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => Role::Client->value,
        ]);

        $this->assertAuthenticated();
    }

    /** @test */
    public function registered_users_are_automatically_logged_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function registered_users_are_assigned_client_role(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->isClient());
        $this->assertEquals(Role::Client, $user->role);
    }

    /** @test */
    public function registered_users_are_assigned_to_default_partner(): void
    {
        $partner = Partner::first();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals($partner->id, $user->partner_id);
    }

    /** @test */
    public function name_is_required_for_registration(): void
    {
        Livewire::test(Register::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['name' => 'required']);
    }

    /** @test */
    public function email_is_required_for_registration(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'required']);
    }

    /** @test */
    public function email_must_be_valid_email_for_registration(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'not-an-email')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'email']);
    }

    /** @test */
    public function email_must_be_unique_for_registration(): void
    {
        $partner = Partner::first();
        User::create([
            'name' => 'Existing User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => Role::Client,
            'partner_id' => $partner->id,
        ]);

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);
    }

    /** @test */
    public function password_is_required_for_registration(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('register')
            ->assertHasErrors(['password' => 'required']);
    }

    /** @test */
    public function password_must_be_at_least_8_characters(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    /** @test */
    public function password_must_be_confirmed(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different-password')
            ->call('register')
            ->assertHasErrors(['password' => 'confirmed']);
    }

    /** @test */
    public function registration_creates_default_partner_if_none_exists(): void
    {
        // Delete all partners
        Partner::truncate();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        // Assert a default partner was created
        $this->assertDatabaseHas('partners', [
            'name' => 'Default Partner',
            'email' => 'partner@domaindesk.com',
            'slug' => 'default-partner',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->partner_id);
    }
}
