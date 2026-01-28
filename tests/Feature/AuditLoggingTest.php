<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_is_created_when_domain_is_created(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
        ]);

        $auditLog = AuditLog::where('auditable_type', Domain::class)
            ->where('auditable_id', $domain->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals($partner->id, $auditLog->partner_id);
        $this->assertNotNull($auditLog->new_values);
        $this->assertNull($auditLog->old_values);
    }

    public function test_audit_log_captures_changes_when_domain_is_updated(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
            'auto_renew' => false,
        ]);

        // Clear the created audit log
        AuditLog::truncate();

        $domain->update(['auto_renew' => true]);

        $auditLog = AuditLog::where('auditable_type', Domain::class)
            ->where('auditable_id', $domain->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals($partner->id, $auditLog->partner_id);
        $this->assertArrayHasKey('auto_renew', $auditLog->old_values);
        $this->assertArrayHasKey('auto_renew', $auditLog->new_values);
        $this->assertFalse($auditLog->old_values['auto_renew']);
        $this->assertTrue($auditLog->new_values['auto_renew']);
    }

    public function test_audit_log_is_created_when_domain_is_soft_deleted(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
        ]);

        AuditLog::truncate();

        $domain->delete();

        $auditLog = AuditLog::where('auditable_type', Domain::class)
            ->where('auditable_id', $domain->id)
            ->where('action', 'soft_deleted')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->user_id);
    }

    public function test_audit_log_captures_ip_and_user_agent(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
        ]);

        $auditLog = AuditLog::where('auditable_type', Domain::class)
            ->where('auditable_id', $domain->id)
            ->first();

        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->user_agent);
    }

    public function test_audit_log_works_with_invoice_model(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
        ]);

        $auditLog = AuditLog::where('auditable_type', Invoice::class)
            ->where('auditable_id', $invoice->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($partner->id, $auditLog->partner_id);
    }

    public function test_audit_log_works_with_user_model(): void
    {
        $partner = Partner::factory()->create();
        $admin = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($admin);
        
        $newUser = User::factory()->create(['partner_id' => $partner->id]);

        $auditLog = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $newUser->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
    }

    public function test_audit_log_works_with_partner_model(): void
    {
        $admin = User::factory()->create();
        
        $this->actingAs($admin);
        
        $partner = Partner::factory()->create();

        $auditLog = AuditLog::where('auditable_type', Partner::class)
            ->where('auditable_id', $partner->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
    }

    public function test_audit_log_works_with_wallet_model(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);

        $auditLog = AuditLog::where('auditable_type', Wallet::class)
            ->where('auditable_id', $wallet->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($partner->id, $auditLog->partner_id);
    }

    public function test_audit_log_works_with_wallet_transaction_model(): void
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $transaction = $wallet->credit(100, 'Test credit', null, null, $user->id);

        $auditLog = AuditLog::where('auditable_type', WalletTransaction::class)
            ->where('auditable_id', $transaction->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($partner->id, $auditLog->partner_id);
    }

    public function test_audit_log_does_not_log_unchanged_updates(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
        ]);

        AuditLog::truncate();

        // Save without changes
        $domain->save();

        $auditLog = AuditLog::where('auditable_type', Domain::class)
            ->where('auditable_id', $domain->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNull($auditLog);
    }

    public function test_audit_log_has_polymorphic_relationship(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        
        $this->actingAs($user);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
        ]);

        $auditLog = $domain->auditLogs()->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($domain->id, $auditLog->auditable_id);
        $this->assertEquals(Domain::class, $auditLog->auditable_type);
        $this->assertInstanceOf(Domain::class, $auditLog->auditable);
    }

    public function test_get_changes_attribute_returns_formatted_changes(): void
    {
        $auditLog = AuditLog::factory()->create([
            'action' => 'updated',
            'old_values' => ['status' => 'active', 'name' => 'old'],
            'new_values' => ['status' => 'expired', 'name' => 'old'],
        ]);

        $changes = $auditLog->changes;

        $this->assertArrayHasKey('status', $changes);
        $this->assertEquals('active', $changes['status']['old']);
        $this->assertEquals('expired', $changes['status']['new']);
        $this->assertArrayNotHasKey('name', $changes); // unchanged field
    }

    public function test_audit_log_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($user->id, $auditLog->user->id);
    }

    public function test_audit_log_belongs_to_partner(): void
    {
        $partner = Partner::factory()->create();
        $auditLog = AuditLog::factory()->create(['partner_id' => $partner->id]);

        $this->assertInstanceOf(Partner::class, $auditLog->partner);
        $this->assertEquals($partner->id, $auditLog->partner->id);
    }

    public function test_audit_log_can_have_null_user_and_partner(): void
    {
        $auditLog = AuditLog::factory()->create([
            'user_id' => null,
            'partner_id' => null,
        ]);

        $this->assertNull($auditLog->user_id);
        $this->assertNull($auditLog->partner_id);
    }
}
