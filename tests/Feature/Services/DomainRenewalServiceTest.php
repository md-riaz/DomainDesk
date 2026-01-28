<?php

namespace Tests\Feature\Services;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PriceAction;
use App\Enums\Role;
use App\Exceptions\RegistrarException;
use App\Jobs\SendRenewalEmailJob;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DomainRenewalService;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class DomainRenewalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DomainRenewalService $service;
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
            'min_years' => 1,
            'max_years' => 10,
        ]);

        // Create TLD prices
        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::RENEW,
            'years' => 1,
            'price' => 12.00,
            'effective_date' => now()->subDay(),
        ]);

        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::RENEW,
            'years' => 2,
            'price' => 22.00,
            'effective_date' => now()->subDay(),
        ]);

        // Create domain expiring in 30 days
        $this->domain = Domain::factory()->create([
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'registered_at' => now()->subYear(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
        ]);

        $this->service = app(DomainRenewalService::class);
    }

    protected function mockSuccessfulRenewalResponse(): void
    {
        $mock = Mockery::mock('overload:' . RegistrarFactory::class);
        $registrarMock = Mockery::mock();
        
        $registrarMock->shouldReceive('renew')
            ->andReturn([
                'success' => true,
                'data' => ['expires_at' => now()->addYears(2)->toIso8601String()],
                'message' => 'Domain renewed successfully',
            ]);
        
        $registrarMock->shouldReceive('getName')
            ->andReturn('MockRegistrar');

        $mock->shouldReceive('make')
            ->andReturn($registrarMock);
    }

    protected function mockFailedRenewalResponse(): void
    {
        $mock = Mockery::mock('overload:' . RegistrarFactory::class);
        $registrarMock = Mockery::mock();
        
        $registrarMock->shouldReceive('renew')
            ->andThrow(new RegistrarException('Renewal failed', 'MockRegistrar'));
        
        $registrarMock->shouldReceive('getName')
            ->andReturn('MockRegistrar');

        $mock->shouldReceive('make')
            ->andReturn($registrarMock);
    }

    public function test_successful_domain_renewal(): void
    {
        $this->mockSuccessfulRenewalResponse();

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully renewed', $result['message']);
        
        // Verify domain updated
        $domain = $result['domain'];
        $this->assertEquals(now()->addDays(30)->addYears(1)->format('Y-m-d'), $domain->expires_at->format('Y-m-d'));
        $this->assertEquals(DomainStatus::Active, $domain->status);
        
        // Verify invoice created and paid
        $invoice = $result['invoice'];
        $this->assertEquals(InvoiceStatus::Paid, $invoice->status);
        $this->assertEquals(12.00, $invoice->total);
        
        // Verify wallet debited
        $this->wallet->refresh();
        $this->assertEquals(988.00, $this->wallet->balance);
        
        // Verify email queued
        Queue::assertPushed(SendRenewalEmailJob::class);
    }

    public function test_renewal_with_multiple_years(): void
    {
        $this->mockSuccessfulRenewalResponse();

        $result = $this->service->renewDomain($this->domain, 2, $this->client->id);

        $this->assertTrue($result['success']);
        
        // Verify expiry extended by 2 years
        $domain = $result['domain'];
        $expectedExpiry = now()->addDays(30)->addYears(2);
        $this->assertEquals($expectedExpiry->format('Y-m-d'), $domain->expires_at->format('Y-m-d'));
        
        // Verify correct price charged
        $invoice = $result['invoice'];
        $this->assertEquals(22.00, $invoice->total);
    }

    public function test_renewal_fails_with_insufficient_balance(): void
    {
        // Empty wallet
        $this->wallet->debit(1000.00, 'Test debit');

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient wallet balance', $result['message']);
        
        // Verify domain not updated
        $this->domain->refresh();
        $this->assertEquals(now()->addDays(30)->format('Y-m-d'), $this->domain->expires_at->format('Y-m-d'));
    }

    public function test_renewal_rollback_on_registrar_failure(): void
    {
        $this->mockFailedRenewalResponse();

        $initialBalance = $this->wallet->balance;
        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Registrar renewal failed', $result['message']);
        
        // Verify wallet refunded
        $this->wallet->refresh();
        $this->assertEquals($initialBalance, $this->wallet->balance);
        
        // Verify invoice marked as failed
        $invoice = $result['invoice'];
        $this->assertEquals(InvoiceStatus::Failed, $invoice->status);
        
        // Verify domain not updated
        $this->domain->refresh();
        $this->assertEquals(now()->addDays(30)->format('Y-m-d'), $this->domain->expires_at->format('Y-m-d'));
    }

    public function test_cannot_renew_too_early(): void
    {
        // Domain expires in 100 days
        $this->domain->update(['expires_at' => now()->addDays(100)]);

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be renewed more than', $result['message']);
    }

    public function test_can_renew_in_grace_period(): void
    {
        $this->mockSuccessfulRenewalResponse();

        // Domain expired 10 days ago (in grace period)
        $this->domain->update(['expires_at' => now()->subDays(10)]);

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertTrue($result['success']);
        
        // Verify grace period surcharge applied (20%)
        $invoice = $result['invoice'];
        $this->assertEquals(14.40, $invoice->total); // 12.00 + 20%
    }

    public function test_cannot_renew_in_redemption_period(): void
    {
        // Domain expired 40 days ago (in redemption period)
        $this->domain->update(['expires_at' => now()->subDays(40)]);

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('redemption period', $result['message']);
    }

    public function test_cannot_renew_deleted_domain(): void
    {
        // Domain expired 70 days ago (deleted)
        $this->domain->update(['expires_at' => now()->subDays(70)]);

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('has been deleted', $result['message']);
    }

    public function test_invalid_renewal_years(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->renewDomain($this->domain, 0, $this->client->id);
    }

    public function test_renewal_years_exceeds_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->renewDomain($this->domain, 11, $this->client->id);
    }

    public function test_check_renewability_active_domain(): void
    {
        $check = $this->service->checkRenewability($this->domain);

        $this->assertTrue($check['renewable']);
        $this->assertFalse($check['in_grace_period']);
    }

    public function test_check_renewability_grace_period(): void
    {
        $this->domain->update(['expires_at' => now()->subDays(10)]);

        $check = $this->service->checkRenewability($this->domain);

        $this->assertTrue($check['renewable']);
        $this->assertTrue($check['in_grace_period']);
    }

    public function test_calculate_renewal_price(): void
    {
        $price = $this->service->calculateRenewalPrice($this->domain, 1);

        $this->assertEquals('12.00', $price);
    }

    public function test_calculate_renewal_price_with_grace_period(): void
    {
        $this->domain->update(['expires_at' => now()->subDays(10)]);

        $price = $this->service->calculateRenewalPrice($this->domain, 1);

        // 12.00 + 20% surcharge
        $this->assertEquals('14.40', $price);
    }

    public function test_process_auto_renewals(): void
    {
        $this->mockSuccessfulRenewalResponse();

        // Create auto-renew domain expiring in 5 days
        $autoRenewDomain = Domain::factory()->create([
            'name' => 'auto-renew.com',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $results = $this->service->processAutoRenewals(7);

        $this->assertEquals(1, $results['processed']);
        $this->assertEquals(1, $results['succeeded']);
        $this->assertEquals(0, $results['failed']);
    }

    public function test_process_auto_renewals_skips_domains_not_expiring_soon(): void
    {
        // Domain expires in 10 days
        $this->domain->update(['auto_renew' => true, 'expires_at' => now()->addDays(10)]);

        $results = $this->service->processAutoRenewals(7); // 7 day lead time

        $this->assertEquals(0, $results['processed']);
    }

    public function test_process_auto_renewals_with_insufficient_balance(): void
    {
        // Empty wallet
        $this->wallet->debit(1000.00, 'Test debit');

        // Create auto-renew domain
        $this->domain->update(['auto_renew' => true, 'expires_at' => now()->addDays(5)]);

        $results = $this->service->processAutoRenewals(7);

        $this->assertEquals(1, $results['processed']);
        $this->assertEquals(0, $results['succeeded']);
        $this->assertEquals(1, $results['failed']);
        
        // Verify failure notification queued
        Queue::assertPushed(SendRenewalEmailJob::class);
    }

    public function test_renewal_creates_audit_log(): void
    {
        $this->mockSuccessfulRenewalResponse();

        $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Domain::class,
            'auditable_id' => $this->domain->id,
            'action' => 'domain_renewed',
            'user_id' => $this->client->id,
        ]);
    }

    public function test_renewal_invoice_has_correct_items(): void
    {
        $this->mockSuccessfulRenewalResponse();

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $invoice = $result['invoice'];
        $this->assertCount(1, $invoice->items);
        
        $item = $invoice->items->first();
        $this->assertStringContainsString('Domain renewal: example.com', $item->description);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals(12.00, $item->total);
    }

    public function test_expired_domain_renews_from_today(): void
    {
        $this->mockSuccessfulRenewalResponse();

        // Domain expired 5 days ago
        $this->domain->update(['expires_at' => now()->subDays(5)]);

        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertTrue($result['success']);
        
        // New expiry should be 1 year from today (not from old expiry)
        $domain = $result['domain'];
        $expectedExpiry = now()->addYears(1);
        $this->assertEquals($expectedExpiry->format('Y-m-d'), $domain->expires_at->format('Y-m-d'));
    }

    public function test_active_domain_extends_from_current_expiry(): void
    {
        $this->mockSuccessfulRenewalResponse();

        $originalExpiry = $this->domain->expires_at->copy();
        
        $result = $this->service->renewDomain($this->domain, 1, $this->client->id);

        $this->assertTrue($result['success']);
        
        // Should extend from original expiry date
        $domain = $result['domain'];
        $expectedExpiry = $originalExpiry->addYears(1);
        $this->assertEquals($expectedExpiry->format('Y-m-d'), $domain->expires_at->format('Y-m-d'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
