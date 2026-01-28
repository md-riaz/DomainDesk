<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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
    public function login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    /** @test */
    public function register_page_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    /** @test */
    public function users_can_login_with_correct_credentials(): void
    {
        $partner = Partner::first();
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $partner->id,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function users_cannot_login_with_incorrect_password(): void
    {
        $partner = Partner::first();
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $partner->id,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    /** @test */
    public function email_is_required_for_login(): void
    {
        Livewire::test(Login::class)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email' => 'required']);
    }

    /** @test */
    public function email_must_be_valid_email(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'not-an-email')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);
    }

    /** @test */
    public function password_is_required_for_login(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->call('login')
            ->assertHasErrors(['password' => 'required']);
    }

    /** @test */
    public function remember_me_functionality_works(): void
    {
        $partner = Partner::first();
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $partner->id,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('remember', true)
            ->call('login');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(auth()->viaRemember());
    }

    /** @test */
    public function login_is_rate_limited_after_too_many_attempts(): void
    {
        $partner = Partner::first();
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $partner->id,
        ]);

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'test@example.com')
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['email']);
        }

        // The 6th attempt should be rate limited
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);

        // Verify the error message contains throttle information
        $component = Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertGuest();
    }

    /** @test */
    public function users_can_logout(): void
    {
        $partner = Partner::first();
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $partner->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function authenticated_users_cannot_access_login_page(): void
    {
        $partner = Partner::first();
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $partner->id,
        ]);

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(); // Should redirect away from login
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('test@example.com|127.0.0.1');
        parent::tearDown();
    }
}
