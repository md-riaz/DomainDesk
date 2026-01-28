<?php

namespace Tests\Feature\Services;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\User;
use App\Services\PartnerOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerOnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PartnerOnboardingService $service;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PartnerOnboardingService();
        
        $this->superAdmin = User::factory()->create([
            'role' => Role::SuperAdmin,
            'partner_id' => null,
        ]);
        
        $this->actingAs($this->superAdmin);
    }

    public function test_can_create_partner_with_all_setup(): void
    {
        $data = [
            'name' => 'Test Partner',
            'email' => 'test@partner.com',
            'initial_balance' => 500.00,
            'status' => 'active',
        ];

        $partner = $this->service->createPartner($data);

        $this->assertInstanceOf(Partner::class, $partner);
        $this->assertEquals('Test Partner', $partner->name);
        $this->assertEquals('test@partner.com', $partner->email);
        $this->assertEquals('active', $partner->status);
        
        // Check wallet was created
        $this->assertNotNull($partner->wallet);
        $this->assertEquals(500.00, $partner->wallet->balance);
        
        // Check admin user was created
        $adminUser = User::where('email', $data['email'])
            ->where('role', Role::Partner)
            ->first();
        $this->assertNotNull($adminUser);
        $this->assertEquals($partner->id, $adminUser->partner_id);
        
        // Check branding was created
        $this->assertNotNull($partner->branding);
        
        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Partner created via admin panel',
            'auditable_type' => Partner::class,
            'auditable_id' => $partner->id,
        ]);
    }

    public function test_can_create_partner_without_initial_balance(): void
    {
        $data = [
            'name' => 'Test Partner',
            'email' => 'test@partner.com',
        ];

        $partner = $this->service->createPartner($data);

        $this->assertNotNull($partner->wallet);
        $this->assertEquals(0, $partner->wallet->balance);
    }

    public function test_can_update_partner(): void
    {
        $partner = Partner::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@email.com',
        ]);

        $updatedPartner = $this->service->updatePartner($partner, [
            'name' => 'New Name',
            'email' => 'new@email.com',
        ]);

        $this->assertEquals('New Name', $updatedPartner->name);
        $this->assertEquals('new@email.com', $updatedPartner->email);
        
        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Partner updated via admin panel',
            'auditable_type' => Partner::class,
            'auditable_id' => $partner->id,
        ]);
    }

    public function test_can_suspend_partner(): void
    {
        $partner = Partner::factory()->create([
            'status' => 'active',
            'is_active' => true,
        ]);

        $suspendedPartner = $this->service->suspendPartner($partner, 'Test reason');

        $this->assertEquals('suspended', $suspendedPartner->status);
        $this->assertFalse($suspendedPartner->is_active);
        
        // Check audit log with reason
        $auditLog = AuditLog::where('auditable_id', $partner->id)
            ->where('action', 'Partner suspended')
            ->first();
            
        $this->assertNotNull($auditLog);
        $this->assertEquals('Test reason', $auditLog->new_values['reason']);
    }

    public function test_can_activate_partner(): void
    {
        $partner = Partner::factory()->create([
            'status' => 'suspended',
            'is_active' => false,
        ]);

        $activatedPartner = $this->service->activatePartner($partner);

        $this->assertEquals('active', $activatedPartner->status);
        $this->assertTrue($activatedPartner->is_active);
        
        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Partner activated',
            'auditable_type' => Partner::class,
            'auditable_id' => $partner->id,
        ]);
    }

    public function test_can_credit_wallet_balance(): void
    {
        $partner = Partner::factory()->create();
        $wallet = $partner->wallet()->create([]);

        $this->service->adjustWalletBalance(
            $partner,
            100.00,
            'Test credit',
            'credit'
        );

        $this->assertEquals(100.00, $wallet->fresh()->balance);
    }

    public function test_can_debit_wallet_balance(): void
    {
        $partner = Partner::factory()->create();
        $wallet = $partner->wallet()->create([]);
        $wallet->credit(200.00, 'Initial', createdBy: $this->superAdmin->id);

        $this->service->adjustWalletBalance(
            $partner,
            50.00,
            'Test debit',
            'debit'
        );

        $this->assertEquals(150.00, $wallet->fresh()->balance);
    }

    public function test_can_adjust_wallet_balance(): void
    {
        $partner = Partner::factory()->create();
        $wallet = $partner->wallet()->create([]);

        $this->service->adjustWalletBalance(
            $partner,
            75.50,
            'Test adjustment',
            'adjustment'
        );

        $this->assertEquals(75.50, $wallet->fresh()->balance);
    }

    public function test_wallet_adjustment_creates_audit_log(): void
    {
        $partner = Partner::factory()->create();
        $wallet = $partner->wallet()->create([]);

        $this->service->adjustWalletBalance(
            $partner,
            100.00,
            'Test reason for adjustment',
            'credit'
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Wallet balance adjusted by admin',
            'auditable_type' => Partner::class,
            'auditable_id' => $partner->id,
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_throws_exception_if_wallet_not_found(): void
    {
        $partner = Partner::factory()->create();
        // Don't create wallet

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Partner wallet not found');

        $this->service->adjustWalletBalance(
            $partner,
            100.00,
            'Test reason',
            'credit'
        );
    }
}
