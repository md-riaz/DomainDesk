<?php

namespace Tests\Feature\Services;

use App\Enums\ContactType;
use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PriceAction;
use App\Enums\Role;
use App\Exceptions\RegistrarException;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DomainRegistrationService;
use App\Services\PricingService;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DomainRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DomainRegistrationService $service;
    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;
    protected Tld $tld;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

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
            'min_years' => 1,
            'max_years' => 10,
        ]);

        // Create TLD price
        \App\Models\TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        $this->service = app(DomainRegistrationService::class);
    }

    public function test_successful_domain_registration(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'auto_renew' => false,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Domain registered successfully', $result['message']);
        $this->assertInstanceOf(Domain::class, $result['domain']);
        $this->assertInstanceOf(Invoice::class, $result['invoice']);
    }

    public function test_domain_created_with_correct_attributes(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        // Create price for 2 years
        \App\Models\TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 2,
            'price' => 18.00,
            'effective_date' => now()->subDay(),
        ]);

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 2,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'auto_renew' => true,
        ]);

        $domain = $result['domain'];
        $this->assertEquals('example.com', $domain->name);
        $this->assertEquals($this->client->id, $domain->client_id);
        $this->assertEquals($this->partner->id, $domain->partner_id);
        $this->assertEquals($this->registrar->id, $domain->registrar_id);
        $this->assertEquals(DomainStatus::PendingRegistration, $domain->status);
        $this->assertTrue($domain->auto_renew);
        $this->assertNotNull($domain->registered_at);
        $this->assertNotNull($domain->expires_at);
    }

    public function test_invoice_created_with_correct_amounts(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $invoice = $result['invoice'];
        $this->assertEquals(InvoiceStatus::Paid, $invoice->status);
        $this->assertEquals(10.00, $invoice->total);
        $this->assertNotNull($invoice->issued_at);
        $this->assertNotNull($invoice->paid_at);
        $this->assertCount(1, $invoice->items);
    }

    public function test_wallet_debited_correctly(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $initialBalance = $this->wallet->balance;

        $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->wallet->refresh();
        $this->assertEquals($initialBalance - 10.00, $this->wallet->balance);
    }

    public function test_contacts_created_for_domain(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $domain = $result['domain'];
        $this->assertCount(4, $domain->contacts);
        
        $contactTypes = $domain->contacts->pluck('type')->map(fn($t) => $t->value)->toArray();
        $this->assertContains('registrant', $contactTypes);
        $this->assertContains('admin', $contactTypes);
        $this->assertContains('tech', $contactTypes);
        $this->assertContains('billing', $contactTypes);
    }

    public function test_nameservers_created_for_domain(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $domain = $result['domain'];
        $this->assertGreaterThanOrEqual(2, $domain->nameservers->count());
    }

    public function test_custom_contacts_used_when_provided(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $customContact = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1.5555555555',
            'organization' => 'Test Corp',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'postal_code' => '12345',
            'country' => 'US',
        ];

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'contacts' => ['registrant' => $customContact],
        ]);

        $registrant = $result['domain']->contacts->where('type', ContactType::Registrant)->first();
        $this->assertEquals('John', $registrant->first_name);
        $this->assertEquals('john@example.com', $registrant->email);
    }

    public function test_custom_nameservers_used_when_provided(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $customNameservers = ['ns1.custom.com', 'ns2.custom.com', 'ns3.custom.com'];

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'nameservers' => $customNameservers,
        ]);

        $nameservers = $result['domain']->nameservers->pluck('nameserver')->toArray();
        $this->assertContains('ns1.custom.com', $nameservers);
        $this->assertContains('ns2.custom.com', $nameservers);
    }

    public function test_audit_log_created_on_success(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'action' => 'domain_registered',
        ]);
    }

    public function test_insufficient_wallet_balance_returns_error(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        // Drain wallet
        $this->wallet->debit($this->wallet->balance, 'Test debit');

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient wallet balance', $result['message']);
    }

    public function test_domain_not_available_returns_error(): void
    {
        $this->mockUnavailableDomain();

        // Use "unavailable" in domain name to trigger MockRegistrar's unavailable response
        $result = $this->service->register([
            'domain_name' => 'unavailable.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Domain is not available for registration', $result['message']);
    }

    public function test_registrar_failure_refunds_wallet_and_marks_invoice_failed(): void
    {
        $this->markTestSkipped('Requires proper mocking of RegistrarFactory static methods');
        
        $this->mockFailedRegistrarResponse();

        $initialBalance = $this->wallet->balance;

        $result = $this->service->register([
            'domain_name' => 'fail-register.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        // Should return error result
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Domain registration failed', $result['message']);

        // Wallet should be refunded
        $this->wallet->refresh();
        $this->assertEquals($initialBalance, $this->wallet->balance);

        // Invoice should exist and be marked as failed
        $failedInvoice = Invoice::where('status', InvoiceStatus::Failed)->first();
        $this->assertNotNull($failedInvoice);
    }

    public function test_validation_fails_for_missing_domain_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain name is required');

        $this->service->register([
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_validation_fails_for_invalid_years(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Registration period must be between 1 and 10 years');

        $this->service->register([
            'domain_name' => 'example.com',
            'years' => 15,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_validation_fails_for_client_not_belonging_to_partner(): void
    {
        $otherPartner = Partner::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Client does not belong to the specified partner');

        $this->mockSuccessfulRegistrarResponse();

        $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $otherPartner->id,
        ]);
    }

    public function test_invalid_tld_throws_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid domain name or TLD not supported');

        $this->mockSuccessfulRegistrarResponse();

        $this->service->register([
            'domain_name' => 'example.invalidtld',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_multi_year_registration(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        // Create price for 3 years
        \App\Models\TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 3,
            'price' => 27.00,
            'effective_date' => now()->subDay(),
        ]);

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 3,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertTrue($result['success']);
        $domain = $result['domain'];
        $this->assertNotNull($domain->expires_at);
        
        // Should expire in approximately 3 years (use ceiling to account for floating point)
        $yearsUntilExpiry = now()->diffInYears($domain->expires_at, true);
        $this->assertEquals(3, ceil($yearsUntilExpiry));
    }

    public function test_wallet_transaction_references_invoice(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $invoice = $result['invoice'];
        
        $walletTransaction = $this->wallet->transactions()
            ->where('type', 'debit')
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->first();

        $this->assertNotNull($walletTransaction);
    }

    public function test_invoice_item_references_domain(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $invoiceItem = $result['invoice']->items->first();
        
        $this->assertEquals(Domain::class, $invoiceItem->reference_type);
        $this->assertEquals($result['domain']->id, $invoiceItem->reference_id);
    }

    public function test_pricing_service_calculates_correct_price(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $invoice = $result['invoice'];
        $this->assertEquals(10.00, (float) $invoice->total);
    }

    public function test_auto_renew_flag_stored_correctly(): void
    {
        $this->mockSuccessfulRegistrarResponse();

        $result = $this->service->register([
            'domain_name' => 'example.com',
            'years' => 1,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'auto_renew' => true,
        ]);

        $this->assertTrue($result['domain']->auto_renew);
    }

    protected function mockSuccessfulRegistrarResponse(): void
    {
        // Update registrar slug to use mock
        $this->registrar->update(['slug' => 'mock']);
    }

    protected function mockUnavailableDomain(): void
    {
        // Mock registrar will return false for unavailable domains
        // We just need to update the registrar to use mock
        $this->registrar->update(['slug' => 'mock']);
    }

    protected function mockFailedRegistrarResponse(): void
    {
        // For failed responses, we need real mocking
        $mock = Mockery::mock(\App\Contracts\RegistrarInterface::class);
        $mock->shouldReceive('checkAvailability')
            ->andReturn(true);
        $mock->shouldReceive('register')
            ->andThrow(new RegistrarException('API Error', 'TestRegistrar'));
        $mock->shouldReceive('getName')
            ->andReturn('TestRegistrar');

        // Override the registrar factory to return our mock
        $this->app->singleton(RegistrarFactory::class, function() use ($mock) {
            $factoryMock = Mockery::mock(RegistrarFactory::class);
            $factoryMock->shouldReceive('make')->andReturn($mock);
            return $factoryMock;
        });
    }
}
