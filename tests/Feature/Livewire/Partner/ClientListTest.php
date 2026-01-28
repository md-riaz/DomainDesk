<?php

namespace Tests\Feature\Livewire\Partner;

use App\Enums\Role;
use App\Livewire\Partner\Client\ClientList;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientListTest extends TestCase
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

    public function test_partner_can_view_client_list()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(ClientList::class)
            ->assertStatus(200)
            ->assertSee('Clients');
    }

    public function test_client_list_displays_clients()
    {
        $this->actingAs($this->partnerUser);

        $clients = User::factory()->count(3)->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        Livewire::test(ClientList::class)
            ->assertSee($clients[0]->name)
            ->assertSee($clients[1]->name)
            ->assertSee($clients[2]->name);
    }

    public function test_search_filters_clients()
    {
        $this->actingAs($this->partnerUser);

        $client1 = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
            'name' => 'John Doe',
        ]);

        $client2 = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
            'name' => 'Jane Smith',
        ]);

        Livewire::test(ClientList::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    public function test_status_filter_works()
    {
        $this->actingAs($this->partnerUser);

        $activeClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $suspendedClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
            'deleted_at' => now(),
        ]);

        Livewire::test(ClientList::class)
            ->set('statusFilter', 'active')
            ->assertSee($activeClient->name)
            ->assertDontSee($suspendedClient->name);

        Livewire::test(ClientList::class)
            ->set('statusFilter', 'suspended')
            ->assertSee($suspendedClient->name)
            ->assertDontSee($activeClient->name);
    }

    public function test_sorting_works()
    {
        $this->actingAs($this->partnerUser);

        $client1 = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
            'name' => 'Alice',
        ]);

        $client2 = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
            'name' => 'Bob',
        ]);

        $component = Livewire::test(ClientList::class)
            ->set('sortBy', 'name')
            ->set('sortDirection', 'asc');

        $component->assertSeeInOrder(['Alice', 'Bob']);

        $component->set('sortDirection', 'desc')
            ->assertSeeInOrder(['Bob', 'Alice']);
    }

    public function test_partner_can_suspend_client()
    {
        $this->actingAs($this->partnerUser);

        $client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        Livewire::test(ClientList::class)
            ->call('suspendClient', $client->id);

        $this->assertSoftDeleted($client);
    }

    public function test_partner_can_activate_client()
    {
        $this->actingAs($this->partnerUser);

        $client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
            'deleted_at' => now(),
        ]);

        Livewire::test(ClientList::class)
            ->call('activateClient', $client->id);

        $this->assertNotSoftDeleted($client);
    }

    public function test_partner_cannot_see_other_partners_clients()
    {
        $otherPartner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $otherClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $otherPartner->id,
        ]);

        $this->actingAs($this->partnerUser);

        Livewire::test(ClientList::class)
            ->assertDontSee($otherClient->name);
    }

    public function test_client_list_shows_domain_count()
    {
        $this->actingAs($this->partnerUser);

        $client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        Domain::factory()->count(3)->create([
            'client_id' => $client->id,
            'partner_id' => $this->partner->id,
        ]);

        Livewire::test(ClientList::class)
            ->assertSee('3');
    }

    public function test_pagination_works()
    {
        $this->actingAs($this->partnerUser);

        User::factory()->count(25)->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        Livewire::test(ClientList::class)
            ->assertSee('Next');
    }

    public function test_client_cannot_access_client_list()
    {
        $client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($client);

        $this->get(route('partner.clients.list'))
            ->assertStatus(403);
    }
}
