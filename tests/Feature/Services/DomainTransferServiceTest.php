<?php

namespace Tests\Feature\Services;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PriceAction;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DomainTransferService;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DomainTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DomainTransferService $transferService;
    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;
    protected Tld $tld;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transferService = app(DomainTransferService::class);

        // Create partner
        $this->partner = Partner::factory()->create();

        // Create registrar
        $this->registrar = Registrar::factory()->create([
            'name' => 'Mock Registrar',
            'api_class' => 'App\Services\Registrar\MockRegistrar',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create client
        $this->client = User::factory()->create([
            'partner_id' => $this->partner->id,
            'role' => 'client',
        ]);

        // Create wallet with balance
        $this->wallet = Wallet::factory()->create([
            'partner_id' => $this->partner->id,
        ]);
        $this->wallet->credit(1000, 'Initial balance', createdBy: $this->client->id);

        // Create TLD and pricing
        $this->tld = Tld::factory()->create([
            'extension' => 'com',
            'is_active' => true,
        ]);

        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::TRANSFER,
            'price' => 12.00,
            'years' => 1,
            'effective_date' => now()->toDateString(),
        ]);
    }

    /** @test */
    public function it_can_initiate_domain_transfer(): void
    {
        Queue::fake();

        $result = $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'auto_renew' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Domain::class, $result['domain']);
        $this->assertInstanceOf(Invoice::class, $result['invoice']);
        $this->assertEquals(DomainStatus::PendingTransfer, $result['domain']->status);
        $this->assertNotNull($result['domain']->auth_code);
        $this->assertNotNull($result['domain']->transfer_initiated_at);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain name is required');

        $this->transferService->initiateTransferIn([
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    /** @test */
    public function it_validates_auth_code_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Auth code must be at least');

        $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'SHORT',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);
    }

    /** @test */
    public function it_checks_wallet_balance_before_transfer(): void
    {
        // Empty wallet
        $this->wallet->transactions()->delete();

        $result = $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient wallet balance', $result['message']);
    }

    /** @test */
    public function it_prevents_duplicate_domain_transfer(): void
    {
        // Create existing domain
        Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
        ]);

        $result = $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }

    /** @test */
    public function it_creates_invoice_for_transfer(): void
    {
        $result = $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertTrue($result['success']);
        $invoice = $result['invoice'];
        $this->assertEquals(InvoiceStatus::Paid, $invoice->status);
        $this->assertStringContainsString('example.com', $invoice->notes);
    }

    /** @test */
    public function it_debits_wallet_on_successful_transfer_initiation(): void
    {
        $initialBalance = $this->wallet->balance;

        $result = $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertTrue($result['success']);
        $this->wallet->refresh();
        $this->assertLessThan($initialBalance, $this->wallet->balance);
    }

    /** @test */
    public function it_encrypts_auth_code(): void
    {
        $authCode = 'AUTH1234567890';

        $result = $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => $authCode,
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertTrue($result['success']);
        $domain = $result['domain'];
        
        // Auth code should be encrypted in database
        $this->assertNotEquals($authCode, $domain->getAttributes()['auth_code']);
        // But decrypted when accessed
        $this->assertEquals($authCode, $domain->auth_code);
    }

    /** @test */
    public function it_can_check_transfer_status(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        $result = $this->transferService->checkTransferStatus($domain);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('status_data', $result);
    }

    /** @test */
    public function it_updates_domain_status_based_on_registrar_response(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(8), // Should be completed
        ]);

        $result = $this->transferService->checkTransferStatus($domain);

        $this->assertTrue($result['success']);
        $domain->refresh();
        $this->assertEquals(DomainStatus::TransferCompleted, $domain->status);
        $this->assertNotNull($domain->transfer_completed_at);
    }

    /** @test */
    public function it_can_cancel_transfer(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        $result = $this->transferService->cancelTransfer($domain, $this->client->id);

        $this->assertTrue($result['success']);
        $domain->refresh();
        $this->assertEquals(DomainStatus::TransferCancelled, $domain->status);
    }

    /** @test */
    public function it_prevents_cancellation_after_window_expires(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(6), // Outside 5-day window
        ]);

        $result = $this->transferService->cancelTransfer($domain, $this->client->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Cancellation window has expired', $result['message']);
    }

    /** @test */
    public function it_refunds_wallet_on_transfer_cancellation(): void
    {
        // Create domain with invoice
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        $invoice = Invoice::factory()->create([
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'status' => InvoiceStatus::Paid,
            'total' => 12.00,
            'notes' => "Domain transfer: example.com",
        ]);

        $initialBalance = $this->wallet->balance;

        $result = $this->transferService->cancelTransfer($domain, $this->client->id);

        $this->assertTrue($result['success']);
        $this->wallet->refresh();
        $this->assertGreaterThan($initialBalance, $this->wallet->balance);
    }

    /** @test */
    public function it_can_generate_auth_code_for_transfer_out(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
        ]);

        $result = $this->transferService->getAuthCodeForTransferOut($domain, $this->client->id);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['auth_code']);
        $this->assertIsString($result['auth_code']);
    }

    /** @test */
    public function it_prevents_unauthorized_auth_code_access(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
        ]);

        $otherUser = User::factory()->create([
            'partner_id' => $this->partner->id,
            'role' => 'client',
        ]);

        $result = $this->transferService->getAuthCodeForTransferOut($domain, $otherUser->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not authorized', $result['message']);
    }

    /** @test */
    public function it_only_generates_auth_code_for_active_domains(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Expired,
        ]);

        $result = $this->transferService->getAuthCodeForTransferOut($domain, $this->client->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('must be active', $result['message']);
    }

    /** @test */
    public function it_creates_audit_log_on_transfer_initiation(): void
    {
        $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'partner_id' => $this->partner->id,
            'user_id' => $this->client->id,
            'action' => 'domain.transfer.initiated',
        ]);
    }

    /** @test */
    public function it_creates_audit_log_on_transfer_cancellation(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        $this->transferService->cancelTransfer($domain, $this->client->id);

        $this->assertDatabaseHas('audit_logs', [
            'partner_id' => $this->partner->id,
            'user_id' => $this->client->id,
            'action' => 'domain.transfer.cancelled',
        ]);
    }

    /** @test */
    public function it_clears_auth_code_after_successful_transfer(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'auth_code' => 'AUTH1234567890',
            'transfer_initiated_at' => now()->subDays(8), // Should be completed
        ]);

        $this->transferService->checkTransferStatus($domain);

        $domain->refresh();
        $this->assertEquals(DomainStatus::TransferCompleted, $domain->status);
        $this->assertNull($domain->auth_code);
    }

    /** @test */
    public function it_cannot_check_status_of_non_transferring_domain(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::Active,
        ]);

        $result = $this->transferService->checkTransferStatus($domain);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not in transferring state', $result['message']);
    }

    /** @test */
    public function it_stores_transfer_metadata(): void
    {
        $result = $this->transferService->initiateTransferIn([
            'domain_name' => 'example.com',
            'auth_code' => 'AUTH1234567890',
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Agent',
        ]);

        $this->assertTrue($result['success']);
        $domain = $result['domain'];
        $this->assertIsArray($domain->transfer_metadata);
        $this->assertArrayHasKey('initiated_by', $domain->transfer_metadata);
        $this->assertEquals('192.168.1.1', $domain->transfer_metadata['ip_address']);
    }
}
