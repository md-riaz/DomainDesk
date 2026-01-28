<?php

namespace Tests\Feature\Livewire;

use App\Enums\DomainStatus;
use App\Livewire\Client\Domain\DomainList;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DomainListTest extends TestCase
{
    use RefreshDatabase;

    private Partner $partner;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->client = User::factory()->client()->create(['partner_id' => $this->partner->id]);
        
        // Set up partner context
        app(\App\Services\PartnerContextService::class)->setPartner($this->partner);
    }

    public function test_domain_list_renders_successfully()
    {
        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSuccessful()
            ->assertSee('My Domains');
    }

    public function test_domain_list_shows_client_domains()
    {
        $domain = Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'example.com',
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSee('example.com');
    }

    public function test_domain_list_does_not_show_other_client_domains()
    {
        $otherClient = User::factory()->client()->create(['partner_id' => $this->partner->id]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'my-domain.com',
        ]);
        
        Domain::factory()->for($this->partner)->for($otherClient, 'client')->create([
            'name' => 'other-domain.com',
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSee('my-domain.com')
            ->assertDontSee('other-domain.com');
    }

    public function test_domain_list_search_filters_domains()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'example.com',
        ]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'test.com',
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->set('search', 'example')
            ->assertSee('example.com')
            ->assertDontSee('test.com');
    }

    public function test_domain_list_status_filter_works()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'active.com',
            'status' => DomainStatus::Active,
        ]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'expired.com',
            'status' => DomainStatus::Expired,
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->set('statusFilter', 'active')
            ->assertSee('active.com')
            ->assertDontSee('expired.com');
    }

    public function test_domain_list_expiring_soon_filter()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'expiring.com',
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'not-expiring.com',
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(90),
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->set('statusFilter', 'expiring_soon')
            ->assertSee('expiring.com')
            ->assertDontSee('not-expiring.com');
    }

    public function test_domain_list_sorting_by_name()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'zebra.com',
            'status' => DomainStatus::Active,
        ]);
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'alpha.com',
            'status' => DomainStatus::Active,
        ]);

        $component = Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->set('sortBy', 'name')
            ->set('sortDirection', 'asc');

        $domains = $component->viewData('domains');
        $this->assertEquals('alpha.com', $domains->first()->name);
    }

    public function test_domain_list_sorting_by_expiry_date()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'later.com',
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(60),
        ]);
        
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'sooner.com',
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(30),
        ]);

        $component = Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->set('sortBy', 'expires_at')
            ->set('sortDirection', 'asc');

        $domains = $component->viewData('domains');
        $this->assertEquals('sooner.com', $domains->first()->name);
    }

    public function test_domain_list_toggle_auto_renew()
    {
        $domain = Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'auto_renew' => false,
            'status' => DomainStatus::Active,
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->call('toggleAutoRenew', $domain->id);

        $this->assertTrue($domain->fresh()->auto_renew);
    }

    public function test_domain_list_pagination_works()
    {
        Domain::factory()->count(25)->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
        ]);

        $component = Livewire::actingAs($this->client)
            ->test(DomainList::class);

        $domains = $component->viewData('domains');
        $this->assertEquals(20, $domains->count());
        $this->assertEquals(25, $domains->total());
    }

    public function test_domain_list_shows_empty_state()
    {
        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSee('No domains found');
    }

    public function test_domain_list_shows_status_badges()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSee('Active');
    }

    public function test_domain_list_sort_direction_toggles()
    {
        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSet('sortDirection', 'asc')
            ->call('sortByColumn', 'name')
            ->assertSet('sortDirection', 'asc')
            ->call('sortByColumn', 'name')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_search_resets_pagination()
    {
        Domain::factory()->count(25)->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->set('page', 2)
            ->set('search', 'test')
            ->assertSet('page', 1);
    }

    public function test_unauthenticated_user_cannot_access_domain_list()
    {
        $this->get(route('client.domains.list'))
            ->assertRedirect(route('login'));
    }

    public function test_client_cannot_toggle_auto_renew_for_other_clients_domain()
    {
        $otherClient = User::factory()->client()->create(['partner_id' => $this->partner->id]);
        $domain = Domain::factory()->for($this->partner)->for($otherClient, 'client')->create([
            'auto_renew' => false,
            'status' => DomainStatus::Active,
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->call('toggleAutoRenew', $domain->id)
            ->assertForbidden();
    }

    public function test_domain_list_displays_expiry_information()
    {
        Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'name' => 'example.com',
            'expires_at' => now()->addDays(15),
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSee('15 days left');
    }

    public function test_domain_list_shows_renewable_domains_with_renew_link()
    {
        $domain = Domain::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => DomainStatus::Active,
        ]);

        Livewire::actingAs($this->client)
            ->test(DomainList::class)
            ->assertSee('Renew');
    }

    public function test_query_string_parameters_are_preserved()
    {
        Livewire::actingAs($this->client)
            ->withQueryParams(['search' => 'example', 'statusFilter' => 'active'])
            ->test(DomainList::class)
            ->assertSet('search', 'example')
            ->assertSet('statusFilter', 'active');
    }
}
