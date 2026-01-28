<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $superAdmin;
    protected User $partnerUser;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a partner
        $this->partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'slug' => 'test-partner',
            'is_active' => true,
        ]);

        // Create users with different roles
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => Role::SuperAdmin,
            'partner_id' => null,
        ]);

        $this->partnerUser = User::create([
            'name' => 'Partner User',
            'email' => 'partner@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Partner,
            'partner_id' => null,
        ]);

        $this->client = User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);
    }

    /** @test */
    public function super_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    /** @test */
    public function partner_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->partnerUser)
            ->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    /** @test */
    public function client_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->client)
            ->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    /** @test */
    public function partner_can_access_partner_dashboard(): void
    {
        $response = $this->actingAs($this->partnerUser)
            ->get(route('partner.dashboard'));

        $response->assertOk();
    }

    /** @test */
    public function super_admin_cannot_access_partner_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('partner.dashboard'));

        $response->assertForbidden();
    }

    /** @test */
    public function client_cannot_access_partner_dashboard(): void
    {
        $response = $this->actingAs($this->client)
            ->get(route('partner.dashboard'));

        $response->assertForbidden();
    }

    /** @test */
    public function client_can_access_client_dashboard(): void
    {
        $response = $this->actingAs($this->client)
            ->get(route('client.dashboard'));

        $response->assertOk();
    }

    /** @test */
    public function super_admin_cannot_access_client_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('client.dashboard'));

        $response->assertForbidden();
    }

    /** @test */
    public function partner_cannot_access_client_dashboard(): void
    {
        $response = $this->actingAs($this->partnerUser)
            ->get(route('client.dashboard'));

        $response->assertForbidden();
    }

    /** @test */
    public function guest_cannot_access_any_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('partner.dashboard'))->assertRedirect(route('login'));
        $this->get(route('client.dashboard'))->assertRedirect(route('login'));
    }

    /** @test */
    public function super_admin_redirects_to_admin_dashboard_after_login(): void
    {
        Livewire::test(Login::class)
            ->set('email', $this->superAdmin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->superAdmin);
    }

    /** @test */
    public function partner_redirects_to_partner_dashboard_after_login(): void
    {
        Livewire::test(Login::class)
            ->set('email', $this->partnerUser->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('partner.dashboard'));

        $this->assertAuthenticatedAs($this->partnerUser);
    }

    /** @test */
    public function client_redirects_to_client_dashboard_after_login(): void
    {
        Livewire::test(Login::class)
            ->set('email', $this->client->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($this->client);
    }
}
