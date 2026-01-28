<?php

namespace Tests\Feature\Livewire;

use App\Enums\PriceAction;
use App\Enums\Role;
use App\Jobs\SendDomainRegistrationEmail;
use App\Livewire\Client\Domain\RegisterDomain;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RegisterDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;
    protected Tld $tld;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create([
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->wallet = Wallet::factory()->create([
            'partner_id' => $this->partner->id,
        ]);
        $this->wallet->credit(1000.00, 'Initial balance');

        $this->client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $this->registrar = Registrar::factory()->create([
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->tld = Tld::factory()->create([
            'extension' => 'com',
            'is_active' => true,
            'min_years' => 1,
            'max_years' => 10,
        ]);

        \App\Models\TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);
    }

    public function test_component_can_render(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->assertStatus(200)
            ->assertSee('Register Domain');
    }

    public function test_initial_step_is_domain_selection(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->assertSet('currentStep', 1)
            ->assertSee('Select Domain');
    }

    public function test_can_mount_with_domain_name(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class, ['domain' => 'example.com'])
            ->assertSet('domainName', 'example.com');
    }

    public function test_can_navigate_to_next_step(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    }

    public function test_can_navigate_to_previous_step(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('previousStep')
            ->assertSet('currentStep', 1);
    }

    public function test_cannot_proceed_without_domain_name(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->call('nextStep')
            ->assertSet('currentStep', 1)
            ->assertSet('errorMessage', 'Please enter a domain name.');
    }

    public function test_cannot_proceed_with_invalid_tld(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.invalidtld')
            ->call('nextStep')
            ->assertSet('errorMessage', 'Invalid domain name or TLD not supported.');
    }

    public function test_price_calculated_when_years_updated(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->set('years', 1)
            ->assertSet('price', '10.00');
    }

    public function test_can_toggle_auto_renew(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('autoRenew', true)
            ->assertSet('autoRenew', true);
    }

    public function test_can_toggle_use_default_contacts(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('useDefaultContacts', false)
            ->assertSet('useDefaultContacts', false);
    }

    public function test_contact_fields_required_when_not_using_defaults(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->set('useDefaultContacts', false)
            ->set('registrantContact', [])
            ->call('nextStep')
            ->assertSet('errorMessage', 'Please fill in all required contact fields.');
    }

    public function test_can_add_nameserver(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->call('addNameserver')
            ->assertCount('nameservers', 3);
    }

    public function test_can_remove_nameserver(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->call('addNameserver')
            ->call('removeNameserver', 2)
            ->assertCount('nameservers', 2);
    }

    public function test_cannot_remove_below_two_nameservers(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->call('removeNameserver', 1)
            ->assertCount('nameservers', 2);
    }

    public function test_nameserver_validation_fails_for_invalid_format(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->set('useDefaultNameservers', false)
            ->set('nameservers', ['invalid nameserver', 'ns2.example.com'])
            ->call('nextStep')
            ->assertSet('errorMessage', 'Invalid nameserver format: invalid nameserver');
    }

    public function test_cannot_register_without_accepting_terms(): void
    {
        $this->mockSuccessfulRegistrar();

        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->set('acceptTerms', false)
            ->call('register')
            ->assertSet('errorMessage', 'You must accept the terms and conditions to proceed.');
    }

    public function test_successful_registration_creates_domain(): void
    {
        Queue::fake();
        $this->mockSuccessfulRegistrar();

        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->set('acceptTerms', true)
            ->call('register');

        $this->assertDatabaseHas('domains', [
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_successful_registration_queues_email(): void
    {
        Queue::fake();
        $this->mockSuccessfulRegistrar();

        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->set('acceptTerms', true)
            ->call('register');

        Queue::assertPushed(SendDomainRegistrationEmail::class);
    }

    public function test_registration_with_insufficient_balance_shows_error(): void
    {
        $this->mockSuccessfulRegistrar();
        
        // Drain wallet
        $this->wallet->debit($this->wallet->balance, 'Drain wallet');

        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->set('acceptTerms', true)
            ->call('register')
            ->assertSet('errorMessage', function ($message) {
                return str_contains($message, 'Insufficient wallet balance');
            });
    }

    public function test_shows_progress_bar(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->assertSee('Domain')
            ->assertSee('Period')
            ->assertSee('Contacts')
            ->assertSee('Nameservers')
            ->assertSee('Review');
    }

    public function test_review_step_shows_summary(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->set('years', 2)
            ->set('autoRenew', true)
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->assertSee('example.com')
            ->assertSee('2 Years')
            ->assertSee('Enabled');
    }

    public function test_processing_state_disables_form(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->set('acceptTerms', true)
            ->set('isProcessing', true)
            ->assertSee('disabled');
    }

    public function test_custom_nameservers_validated(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->set('useDefaultNameservers', false)
            ->set('nameservers', ['ns1.custom.com'])
            ->call('nextStep')
            ->assertSet('errorMessage', 'Please provide at least 2 nameservers.');
    }

    public function test_multi_step_form_validates_each_step(): void
    {
        $component = Livewire::actingAs($this->client)
            ->test(RegisterDomain::class);

        // Step 1
        $component->call('nextStep')
            ->assertSet('currentStep', 1);

        // With valid domain
        $component->set('domainName', 'example.com')
            ->call('nextStep')
            ->assertSet('currentStep', 2);

        // Step 2 to 3
        $component->call('nextStep')
            ->assertSet('currentStep', 3);

        // Step 3 to 4
        $component->call('nextStep')
            ->assertSet('currentStep', 4);

        // Step 4 to 5
        $component->call('nextStep')
            ->assertSet('currentStep', 5);
    }

    public function test_email_validation_in_custom_contacts(): void
    {
        Livewire::actingAs($this->client)
            ->test(RegisterDomain::class)
            ->set('domainName', 'example.com')
            ->call('nextStep')
            ->call('nextStep')
            ->set('useDefaultContacts', false)
            ->set('registrantContact', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'invalid-email',
                'phone' => '+1.5555555555',
                'address' => '123 Main St',
                'city' => 'City',
                'state' => 'State',
                'postal_code' => '12345',
                'country' => 'US',
            ])
            ->call('nextStep')
            ->assertSet('errorMessage', 'Please enter a valid email address.');
    }

    public function test_max_nameservers_limited_to_four(): void
    {
        $component = Livewire::actingAs($this->client)
            ->test(RegisterDomain::class);

        $component->call('addNameserver')
            ->call('addNameserver')
            ->assertCount('nameservers', 4);

        $component->call('addNameserver')
            ->assertCount('nameservers', 4);
    }

    protected function mockSuccessfulRegistrar(): void
    {
        // Update registrar slug to use mock
        $this->registrar->update(['slug' => 'mock']);
    }
}
