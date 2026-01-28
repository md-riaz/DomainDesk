<?php

namespace Tests\Feature\Livewire;

use App\Enums\DomainStatus;
use App\Enums\PriceAction;
use App\Enums\Role;
use App\Jobs\SendRenewalEmailJob;
use App\Livewire\Client\Domain\RenewDomain;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RenewDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;
    protected Tld $tld;
    protected Wallet $wallet;
    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        // Create partner
        $this->partner = Partner::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        // Create wallet with balance
        $this->wallet = Wallet::factory()->create([
            'partner_id' => $this->partner->id,
        ]);
        $this->wallet->credit(1000.00, 'Initial balance');

        // Create client
        $this->client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        // Create registrar
        $this->registrar = Registrar::factory()->create([
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create TLD
        $this->tld = Tld::factory()->create([
            'extension' => 'com',
            'is_active' => true,
        ]);

        // Create TLD prices
        for ($i = 1; $i <= 10; $i++) {
            TldPrice::factory()->create([
                'tld_id' => $this->tld->id,
                'action' => PriceAction::RENEW,
                'years' => $i,
                'price' => 10.00 + ($i - 1),
                'effective_date' => now()->subDay(),
            ]);
        }

        // Create domain
        $this->domain = Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
        ]);

        $this->mockSuccessfulRenewalResponse();
    }

    protected function mockSuccessfulRenewalResponse(): void
    {
        $mock = Mockery::mock('overload:' . RegistrarFactory::class);
        $registrarMock = Mockery::mock();
        
        $registrarMock->shouldReceive('renew')
            ->andReturn([
                'success' => true,
                'data' => ['expires_at' => now()->addYears(1)->toIso8601String()],
                'message' => 'Domain renewed successfully',
            ]);
        
        $registrarMock->shouldReceive('getName')
            ->andReturn('MockRegistrar');

        $mock->shouldReceive('make')
            ->andReturn($registrarMock);
    }

    public function test_component_renders_successfully(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertStatus(200)
            ->assertSee('Renew Domain')
            ->assertSee($this->domain->name)
            ->assertSee($this->domain->expires_at->format('M j, Y'));
    }

    public function test_component_requires_authentication(): void
    {
        $this->get(route('client.domains.renew', $this->domain))
            ->assertRedirect(route('login'));
    }

    public function test_component_prevents_unauthorized_access(): void
    {
        $otherClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($otherClient);

        $this->get(route('client.domains.renew', $this->domain))
            ->assertForbidden();
    }

    public function test_displays_domain_information(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee($this->domain->name)
            ->assertSee($this->domain->expires_at->format('M j, Y'));
    }

    public function test_calculates_renewal_price(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSet('price', '10.00')
            ->assertSee('$10.00');
    }

    public function test_updates_price_when_years_changed(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSet('years', 1)
            ->assertSet('price', '10.00')
            ->set('years', 2)
            ->assertSet('price', '11.00');
    }

    public function test_displays_wallet_balance(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee('$1,000.00');
    }

    public function test_successful_renewal(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->set('years', 1)
            ->call('renewDomain')
            ->assertHasNoErrors()
            ->assertSet('successMessage', fn($value) => str_contains($value, 'successfully renewed'));

        Queue::assertPushed(SendRenewalEmailJob::class);
    }

    public function test_shows_error_for_insufficient_balance(): void
    {
        // Empty wallet
        $this->wallet->debit(1000.00, 'Test debit');

        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->set('years', 1)
            ->call('renewDomain')
            ->assertSet('errorMessage', fn($value) => str_contains($value, 'Insufficient wallet balance'));
    }

    public function test_shows_loading_state_during_renewal(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->set('years', 1)
            ->call('renewDomain')
            ->assertSet('isProcessing', false);
    }

    public function test_validates_renewal_years(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->set('years', 0)
            ->call('renewDomain')
            ->assertSet('errorMessage', fn($value) => str_contains($value, 'between 1 and 10'));
    }

    public function test_shows_grace_period_warning(): void
    {
        // Domain expired 10 days ago
        $this->domain->update(['expires_at' => now()->subDays(10)]);

        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee('Grace Period')
            ->assertSee('20% surcharge');
    }

    public function test_shows_non_renewable_error(): void
    {
        // Domain expired 70 days ago (deleted)
        $this->domain->update(['expires_at' => now()->subDays(70)]);

        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee('Domain Cannot Be Renewed')
            ->assertSee('has been deleted');
    }

    public function test_displays_renewal_period_selector(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee('1 Year')
            ->assertSee('2 Years')
            ->assertSee('10 Years');
    }

    public function test_displays_new_expiry_date(): void
    {
        $this->actingAs($this->client);

        $expectedNewExpiry = $this->domain->expires_at->copy()->addYears(1);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee('New Expiry Date')
            ->assertSee($expectedNewExpiry->format('M j, Y'));
    }

    public function test_disables_renew_button_with_insufficient_balance(): void
    {
        // Empty wallet
        $this->wallet->debit(1000.00, 'Test debit');

        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee('Insufficient balance');
    }

    public function test_redirects_after_successful_renewal(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->set('years', 1)
            ->call('renewDomain')
            ->assertRedirect(route('client.domains.show', $this->domain));
    }

    public function test_check_renewability_updates(): void
    {
        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSet('renewabilityCheck', fn($value) => $value['renewable'] === true)
            ->call('checkRenewability')
            ->assertSet('renewabilityCheck', fn($value) => is_array($value) && $value['renewable'] === true);
    }

    public function test_displays_expiry_urgency_colors(): void
    {
        // Domain expiring in 5 days
        $this->domain->update(['expires_at' => now()->addDays(5)]);

        $this->actingAs($this->client);

        Livewire::test(RenewDomain::class, ['domain' => $this->domain])
            ->assertSee($this->domain->expires_at->format('M j, Y'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
