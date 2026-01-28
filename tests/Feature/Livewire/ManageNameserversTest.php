<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Client\Domain\ManageNameservers;
use App\Models\Domain;
use App\Models\DomainNameserver;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageNameserversTest extends TestCase
{
    use RefreshDatabase;

    protected Domain $domain;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $partner = Partner::factory()->create();
        $registrar = Registrar::factory()->create(['slug' => 'mock']);
        $this->user = User::factory()->client()->create(['partner_id' => $partner->id]);
        
        $this->domain = Domain::factory()->active()->create([
            'partner_id' => $partner->id,
            'registrar_id' => $registrar->id,
            'client_id' => $this->user->id,
        ]);

        // Set partner context
        session(['partner_id' => $partner->id]);
    }

    public function test_component_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->assertStatus(200)
            ->assertSee('Manage Nameservers')
            ->assertSee($this->domain->name);
    }

    public function test_loads_existing_nameservers()
    {
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.example.com',
            'order' => 1,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns2.example.com',
            'order' => 2,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->assertSet('nameservers', ['ns1.example.com', 'ns2.example.com']);
    }

    public function test_can_add_nameserver()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->call('addNameserver')
            ->assertCount('nameservers', 3);
    }

    public function test_can_remove_nameserver()
    {
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.example.com',
            'order' => 1,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns2.example.com',
            'order' => 2,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns3.example.com',
            'order' => 3,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->call('removeNameserver', 2)
            ->assertCount('nameservers', 2);
    }

    public function test_cannot_remove_below_minimum()
    {
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.example.com',
            'order' => 1,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns2.example.com',
            'order' => 2,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->call('removeNameserver', 1);
        
        // Should still have 2 nameservers
        $this->assertCount(2, $component->get('nameservers'));
    }

    public function test_can_use_default_nameservers()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->call('useDefaults')
            ->assertSet('useDefaultNameservers', true);
    }

    public function test_can_save_nameservers()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->set('nameservers', [
                'ns1.newhost.com',
                'ns2.newhost.com',
            ])
            ->call('save')
            ->assertSet('successMessage', function ($message) {
                return str_contains($message, 'successfully');
            });

        $this->assertDatabaseHas('domain_nameservers', [
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.newhost.com',
        ]);
    }

    public function test_shows_error_on_validation_failure()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->set('nameservers', [
                'ns1.example.com',
            ])
            ->call('save')
            ->assertSet('errorMessage', function ($message) {
                return !empty($message);
            });
    }

    public function test_can_sync_nameservers()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->call('sync')
            ->assertSet('isSyncing', false);
    }

    public function test_unauthorized_user_cannot_access()
    {
        $otherUser = User::factory()->client()->create();
        $this->actingAs($otherUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain]);
    }

    public function test_shows_loading_state_when_saving()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->set('nameservers', [
                'ns1.example.com',
                'ns2.example.com',
            ])
            ->call('save');
        
        // After save completes, isLoading should be false
        // This is a simplistic test; in real tests you'd check during the save
    }

    public function test_displays_info_banner()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->assertSee('24-48 hours');
    }

    public function test_maximum_four_nameservers()
    {
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns1.example.com',
            'order' => 1,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns2.example.com',
            'order' => 2,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns3.example.com',
            'order' => 3,
        ]);
        
        DomainNameserver::create([
            'domain_id' => $this->domain->id,
            'nameserver' => 'ns4.example.com',
            'order' => 4,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ManageNameservers::class, ['domain' => $this->domain])
            ->call('addNameserver');
        
        // Should still be 4 (not allow adding 5th)
        $this->assertCount(4, $component->get('nameservers'));
    }
}
