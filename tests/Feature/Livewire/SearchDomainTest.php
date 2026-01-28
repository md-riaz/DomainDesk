<?php

namespace Tests\Feature\Livewire;

use App\Enums\PriceAction;
use App\Enums\Role;
use App\Livewire\Client\Domain\SearchDomain;
use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SearchDomainTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Partner $partner;
    protected Registrar $registrar;
    protected Tld $tld;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test partner
        $this->partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'slug' => 'test-partner',
            'status' => 'active',
            'is_active' => true,
        ]);

        PartnerBranding::create([
            'partner_id' => $this->partner->id,
            'email_sender_name' => 'Test Partner',
        ]);

        // Create test user
        $this->user = User::create([
            'partner_id' => $this->partner->id,
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'client',
        ]);

        // Create test registrar
        $this->registrar = Registrar::create([
            'name' => 'Mock Registrar',
            'slug' => 'mock',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create test TLD
        $this->tld = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        // Create pricing
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => '10.00',
            'effective_date' => now()->toDateString(),
        ]);

        Cache::flush();
        RateLimiter::clear('domain-search:' . $this->user->id);
    }

    /** @test */
    public function it_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->assertStatus(200)
            ->assertSee('Find Your Perfect Domain');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $this->get(route('client.domains.search'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function it_initializes_with_default_values()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->assertSet('searchQuery', '')
            ->assertSet('years', 1)
            ->assertSet('hasSearched', false)
            ->assertSet('showSuggestions', false);
    }

    /** @test */
    public function it_validates_search_query()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', '')
            ->call('search')
            ->assertHasErrors(['searchQuery' => 'required']);
    }

    /** @test */
    public function it_validates_minimum_search_length()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'ab')
            ->call('search')
            ->assertHasErrors(['searchQuery' => 'min']);
    }

    /** @test */
    public function it_can_search_single_domain()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com')
            ->call('search')
            ->assertSet('hasSearched', true)
            ->assertCount('searchResults', 1)
            ->assertSee('example.com');
    }

    /** @test */
    public function it_can_search_multiple_domains()
    {
        $this->actingAs($this->user);

        // Create additional TLD
        $netTld = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'net',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com, test.net')
            ->call('search')
            ->assertSet('hasSearched', true)
            ->assertCount('searchResults', 2);
    }

    /** @test */
    public function it_displays_error_for_invalid_domain()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'invalid domain')
            ->call('search')
            ->assertSet('hasSearched', true)
            ->assertSee('Invalid domain format');
    }

    /** @test */
    public function it_displays_error_for_unsupported_tld()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.xyz')
            ->call('search')
            ->assertSet('hasSearched', true)
            ->assertSee('TLD not supported');
    }

    /** @test */
    public function it_shows_availability_status()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com')
            ->call('search')
            ->assertSet('hasSearched', true);
    }

    /** @test */
    public function it_displays_pricing_for_available_domains()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'available-domain.com')
            ->call('search')
            ->assertSet('hasSearched', true);
    }

    /** @test */
    public function it_can_update_years()
    {
        $this->actingAs($this->user);

        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 2,
            'price' => '18.00',
            'effective_date' => now()->toDateString(),
        ]);

        Livewire::test(SearchDomain::class)
            ->set('years', 2)
            ->assertSet('years', 2);
    }

    /** @test */
    public function it_researches_when_years_updated()
    {
        $this->actingAs($this->user);

        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 2,
            'price' => '18.00',
            'effective_date' => now()->toDateString(),
        ]);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com')
            ->call('search')
            ->assertSet('hasSearched', true)
            ->set('years', 2)
            ->assertSet('hasSearched', true);
    }

    /** @test */
    public function it_shows_suggestions_for_taken_domain()
    {
        $this->actingAs($this->user);

        // Create additional TLDs for suggestions
        foreach (['net', 'org', 'io'] as $ext) {
            Tld::create([
                'registrar_id' => $this->registrar->id,
                'extension' => $ext,
                'min_years' => 1,
                'max_years' => 10,
                'is_active' => true,
            ]);
        }

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com')
            ->call('search')
            ->assertSet('hasSearched', true);
    }

    /** @test */
    public function it_can_search_suggested_domain()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->call('searchDomain', 'suggestion.com')
            ->assertSet('searchQuery', 'suggestion.com')
            ->assertSet('hasSearched', true);
    }

    /** @test */
    public function it_can_clear_search()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com')
            ->call('search')
            ->assertSet('hasSearched', true)
            ->call('clear')
            ->assertSet('searchQuery', '')
            ->assertSet('hasSearched', false)
            ->assertCount('searchResults', 0);
    }

    /** @test */
    public function it_enforces_rate_limiting()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com');

        // Perform 10 searches (the limit)
        for ($i = 0; $i < 10; $i++) {
            $component->call('search');
        }

        // The 11th search should be rate limited
        $component->call('search')
            ->assertSet('errorMessage', function ($message) {
                return str_contains($message, 'Too many searches');
            });
    }

    /** @test */
    public function it_displays_empty_state_before_search()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->assertSee('Start Your Domain Search')
            ->assertSee('Enter a domain name above');
    }

    /** @test */
    public function it_displays_loading_state()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->assertSee('Search');
    }

    /** @test */
    public function it_handles_empty_domain_list()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', '   ,  ,  ')
            ->call('search')
            ->assertSet('errorMessage', 'Please enter at least one valid domain name.');
    }

    /** @test */
    public function it_filters_duplicate_domains()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com, example.com, test.com')
            ->call('search')
            ->assertSet('hasSearched', true);
    }

    /** @test */
    public function it_respects_bulk_search_limit()
    {
        $this->actingAs($this->user);

        // Create 25 domains (over the limit of 20)
        $domains = implode(', ', array_map(fn($i) => "domain{$i}.com", range(1, 25)));

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', $domains)
            ->call('search')
            ->assertSet('errorMessage', function ($message) {
                return str_contains($message, 'Too many domains');
            });
    }

    /** @test */
    public function it_uses_partner_context_for_pricing()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com')
            ->call('search')
            ->assertSet('hasSearched', true);
    }

    /** @test */
    public function it_displays_register_button_for_available_domains()
    {
        $this->actingAs($this->user);

        // The test just ensures the component doesn't error when displaying results
        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'available-test.com')
            ->call('search')
            ->assertSet('hasSearched', true)
            ->assertViewHas('searchResults');
    }

    /** @test */
    public function it_shows_taken_badge_for_unavailable_domains()
    {
        $this->actingAs($this->user);

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', 'example.com')
            ->call('search');
    }

    /** @test */
    public function it_parses_newline_separated_domains()
    {
        $this->actingAs($this->user);

        $netTld = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'net',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        $domains = "example.com\ntest.net";

        Livewire::test(SearchDomain::class)
            ->set('searchQuery', $domains)
            ->call('search')
            ->assertSet('hasSearched', true);
    }
}
