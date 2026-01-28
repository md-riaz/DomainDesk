<?php

namespace Tests\Feature;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Scopes\PartnerScope;
use App\Services\PartnerContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner1;
    protected Partner $partner2;
    protected User $superAdmin;
    protected User $partner1User;
    protected User $partner2User;
    protected User $client1;
    protected User $client2;
    protected Wallet $wallet1;
    protected Wallet $wallet2;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset partner context service to avoid test pollution
        app(PartnerContextService::class)->reset();

        // Create two partners
        $this->partner1 = Partner::factory()->create(['name' => 'Partner 1']);
        $this->partner2 = Partner::factory()->create(['name' => 'Partner 2']);

        // Create wallets for partners
        $this->wallet1 = Wallet::factory()->create(['partner_id' => $this->partner1->id]);
        $this->wallet2 = Wallet::factory()->create(['partner_id' => $this->partner2->id]);

        // Create users with different roles
        $this->superAdmin = User::factory()->create([
            'role' => Role::SuperAdmin,
            'partner_id' => null,
        ]);

        $this->partner1User = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $this->partner1->id,
        ]);

        $this->partner2User = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $this->partner2->id,
        ]);

        $this->client1 = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner1->id,
        ]);

        $this->client2 = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner2->id,
        ]);
    }

    /** @test */
    public function partner_scope_applies_automatically_to_domains()
    {
        // Create domains for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Should only see partner 1's domains
        $domains = Domain::all();
        $this->assertCount(1, $domains);
        $this->assertEquals($this->partner1->id, $domains->first()->partner_id);
    }

    /** @test */
    public function partner_scope_applies_automatically_to_invoices()
    {
        // Create invoices for both partners
        Invoice::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Invoice::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Should only see partner 1's invoices
        $invoices = Invoice::all();
        $this->assertCount(1, $invoices);
        $this->assertEquals($this->partner1->id, $invoices->first()->partner_id);
    }

    /** @test */
    public function partner_scope_applies_automatically_to_wallet_transactions()
    {
        // Create transactions for both partners
        WalletTransaction::factory()->create([
            'wallet_id' => $this->wallet1->id,
            'partner_id' => $this->partner1->id,
        ]);
        WalletTransaction::factory()->create([
            'wallet_id' => $this->wallet2->id,
            'partner_id' => $this->partner2->id,
        ]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Should only see partner 1's transactions
        $transactions = WalletTransaction::all();
        $this->assertCount(1, $transactions);
        $this->assertEquals($this->partner1->id, $transactions->first()->partner_id);
    }

    /** @test */
    public function partner_scope_applies_to_client_users()
    {
        // Create additional clients for both partners
        User::factory()->create(['role' => Role::Client, 'partner_id' => $this->partner1->id]);
        User::factory()->create(['role' => Role::Client, 'partner_id' => $this->partner2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Should only see partner 1's clients (plus partner1User itself)
        $users = User::whereClient()->get();
        $this->assertCount(2, $users); // client1 + new client
        $users->each(function ($user) {
            $this->assertEquals($this->partner1->id, $user->partner_id);
        });
    }

    /** @test */
    public function cross_partner_access_is_blocked_by_id()
    {
        // Create domain for partner 2
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner2->id,
            'client_id' => $this->client2->id,
        ]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Try to access partner 2's domain directly by ID
        $result = Domain::find($domain->id);
        $this->assertNull($result);
    }

    /** @test */
    public function cross_partner_access_is_blocked_by_where_clause()
    {
        // Create domains for partner 2
        Domain::factory()->count(3)->create([
            'partner_id' => $this->partner2->id,
            'client_id' => $this->client2->id,
        ]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Try various query methods - all should return empty
        $this->assertCount(0, Domain::where('partner_id', $this->partner2->id)->get());
        $this->assertCount(0, Domain::whereIn('partner_id', [$this->partner2->id])->get());
        $this->assertCount(0, Domain::all());
    }

    /** @test */
    public function super_admin_can_see_all_partners_data()
    {
        // Create data for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);
        Invoice::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Invoice::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as super admin
        $this->actingAs($this->superAdmin);

        // Should see all data
        $this->assertCount(2, Domain::all());
        $this->assertCount(2, Invoice::all());
    }

    /** @test */
    public function super_admin_bypasses_scope_on_specific_queries()
    {
        // Create domain for partner 2
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner2->id,
            'client_id' => $this->client2->id,
        ]);

        // Act as super admin
        $this->actingAs($this->superAdmin);

        // Should be able to access any domain directly
        $result = Domain::find($domain->id);
        $this->assertNotNull($result);
        $this->assertEquals($domain->id, $result->id);
    }

    /** @test */
    public function without_partner_scope_allows_cross_partner_queries()
    {
        // Create domains for both partners
        $domain1 = Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        $domain2 = Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Use withoutPartnerScope to see all domains
        $domains = Domain::withoutPartnerScope()->get();
        $this->assertCount(2, $domains);
    }

    /** @test */
    public function for_partner_scope_queries_specific_partner()
    {
        // Create domains for both partners
        Domain::factory()->count(2)->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->count(3)->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as super admin (to bypass automatic scope)
        $this->actingAs($this->superAdmin);

        // Query for specific partner
        $partner1Domains = Domain::forPartner($this->partner1->id)->get();
        $partner2Domains = Domain::forPartner($this->partner2->id)->get();

        $this->assertCount(2, $partner1Domains);
        $this->assertCount(3, $partner2Domains);
    }

    /** @test */
    public function for_current_partner_uses_context_service()
    {
        // Create domains for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Set partner context
        $partnerContext = app(PartnerContextService::class);
        $partnerContext->setPartner($this->partner1);

        // Query using forCurrentPartner
        $domains = Domain::forCurrentPartner()->get();
        $this->assertCount(1, $domains);
        $this->assertEquals($this->partner1->id, $domains->first()->partner_id);
    }

    /** @test */
    public function scope_applies_to_relationship_queries()
    {
        // Create domains for both partners
        $domain1 = Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        $domain2 = Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Query domains through partner relationship
        $partnerDomains = Partner::find($this->partner1->id)->clientDomains;
        $this->assertCount(1, $partnerDomains);
    }

    /** @test */
    public function scope_applies_with_eager_loading()
    {
        // Create domains with invoices for both partners
        $domain1 = Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        $domain2 = Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);
        
        Invoice::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Invoice::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Eager load with scope
        $clients = User::whereClient()->with('domains', 'invoices')->get();
        $this->assertCount(1, $clients); // Only client1
        $this->assertCount(1, $clients->first()->domains);
        $this->assertCount(1, $clients->first()->invoices);
    }

    /** @test */
    public function scope_applies_with_counts()
    {
        // Create multiple domains for both partners
        Domain::factory()->count(3)->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->count(5)->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Count should only include partner 1's domains
        $count = Domain::count();
        $this->assertEquals(3, $count);
    }

    /** @test */
    public function scope_applies_with_aggregates()
    {
        // Create invoices for both partners with explicit invoice numbers
        Invoice::factory()->create([
            'partner_id' => $this->partner1->id,
            'client_id' => $this->client1->id,
            'total' => 100,
            'invoice_number' => 'INV-AGG-001',
        ]);
        Invoice::factory()->create([
            'partner_id' => $this->partner1->id,
            'client_id' => $this->client1->id,
            'total' => 200,
            'invoice_number' => 'INV-AGG-002',
        ]);
        Invoice::factory()->create([
            'partner_id' => $this->partner2->id,
            'client_id' => $this->client2->id,
            'total' => 500,
            'invoice_number' => 'INV-AGG-003',
        ]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Aggregates should only include partner 1's data
        $sum = Invoice::sum('total');
        $avg = Invoice::avg('total');
        $max = Invoice::max('total');

        $this->assertEquals(300, $sum);
        $this->assertEquals(150, $avg);
        $this->assertEquals(200, $max);
    }

    /** @test */
    public function partner_id_is_automatically_set_on_creation()
    {
        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Create domain without explicitly setting partner_id
        $domain = Domain::create([
            'name' => 'example.com',
            'client_id' => $this->client1->id,
            'status' => DomainStatus::Active,
            'registered_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // partner_id should be automatically set
        $this->assertEquals($this->partner1->id, $domain->partner_id);
    }

    /** @test */
    public function partner_id_cannot_be_changed_after_creation()
    {
        // Create domain for partner 1
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner1->id,
            'client_id' => $this->client1->id,
        ]);

        // Try to change partner_id
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('partner_id cannot be changed');

        $domain->update(['partner_id' => $this->partner2->id]);
    }

    /** @test */
    public function creating_entity_without_partner_id_throws_exception()
    {
        // Don't authenticate (no user context)
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('partner_id is required');

        // Try to create domain without partner_id and no user context
        Domain::create([
            'name' => 'example.com',
            'client_id' => $this->client1->id,
            'status' => DomainStatus::Active,
            'registered_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    /** @test */
    public function belongs_to_partner_method_works_correctly()
    {
        // Create domain for partner 1
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner1->id,
            'client_id' => $this->client1->id,
        ]);

        // Test belongsToPartner method
        $this->assertTrue($domain->belongsToPartner($this->partner1));
        $this->assertTrue($domain->belongsToPartner($this->partner1->id));
        $this->assertFalse($domain->belongsToPartner($this->partner2));
        $this->assertFalse($domain->belongsToPartner($this->partner2->id));
    }

    /** @test */
    public function belongs_to_current_partner_method_works_correctly()
    {
        // Create domain for partner 1
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner1->id,
            'client_id' => $this->client1->id,
        ]);

        // Set partner context
        $partnerContext = app(PartnerContextService::class);
        $partnerContext->setPartner($this->partner1);

        // Test belongsToCurrentPartner method
        $this->assertTrue($domain->belongsToCurrentPartner());

        // Change context
        $partnerContext->setPartner($this->partner2);
        $this->assertFalse($domain->belongsToCurrentPartner());
    }

    /** @test */
    public function concurrent_requests_maintain_isolation()
    {
        // Create domains for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Simulate concurrent requests by switching users
        $this->actingAs($this->partner1User);
        $partner1Count = Domain::count();

        $this->actingAs($this->partner2User);
        $partner2Count = Domain::count();

        $this->actingAs($this->partner1User);
        $partner1CountAgain = Domain::count();

        // Each should see only their own data
        $this->assertEquals(1, $partner1Count);
        $this->assertEquals(1, $partner2Count);
        $this->assertEquals(1, $partner1CountAgain);
    }

    /** @test */
    public function scope_applies_with_complex_queries()
    {
        // Create domains with various statuses for both partners
        Domain::factory()->create([
            'partner_id' => $this->partner1->id,
            'client_id' => $this->client1->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(10),
        ]);
        Domain::factory()->create([
            'partner_id' => $this->partner2->id,
            'client_id' => $this->client2->id,
            'status' => DomainStatus::Active,
            'expires_at' => now()->addDays(10),
        ]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Complex query with scopes
        $expiring = Domain::active()->expiring(30)->get();
        $this->assertCount(1, $expiring);
        $this->assertEquals($this->partner1->id, $expiring->first()->partner_id);
    }

    /** @test */
    public function scope_prevents_data_leakage_through_exists_queries()
    {
        // Create domain for partner 2
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner2->id,
            'client_id' => $this->client2->id,
        ]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Try to check existence of partner 2's domain
        $exists = Domain::where('id', $domain->id)->exists();
        $this->assertFalse($exists);
    }

    /** @test */
    public function scope_prevents_data_leakage_through_first_or_fail()
    {
        // Create domain for partner 2
        $domain = Domain::factory()->create([
            'partner_id' => $this->partner2->id,
            'client_id' => $this->client2->id,
        ]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Try to access partner 2's domain with firstOrFail
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        Domain::where('id', $domain->id)->firstOrFail();
    }

    /** @test */
    public function client_users_see_only_their_partner_data()
    {
        // Create domains for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Act as client 1 (belongs to partner 1)
        $this->actingAs($this->client1);

        // Should only see partner 1's domains
        $domains = Domain::all();
        $this->assertCount(1, $domains);
        $this->assertEquals($this->partner1->id, $domains->first()->partner_id);
    }

    /** @test */
    public function scope_applies_to_soft_deleted_queries()
    {
        // Create and soft delete domains for both partners
        $domain1 = Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        $domain2 = Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);
        
        $domain1->delete();
        $domain2->delete();

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Should only see partner 1's soft deleted domains
        $trashed = Domain::onlyTrashed()->get();
        $this->assertCount(1, $trashed);
        $this->assertEquals($this->partner1->id, $trashed->first()->partner_id);
    }

    /** @test */
    public function partner_context_service_integration_works()
    {
        // Create domains for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        // Set partner context via service
        $partnerContext = app(PartnerContextService::class);
        $partnerContext->setPartner($this->partner1);

        // Don't authenticate user - context should come from service
        $domains = Domain::all();
        $this->assertCount(1, $domains);
        $this->assertEquals($this->partner1->id, $domains->first()->partner_id);
    }

    /** @test */
    public function scope_with_raw_queries_still_applies()
    {
        // Create domains for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id, 'name' => 'test1.com']);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id, 'name' => 'test2.com']);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // Use whereRaw
        $domains = Domain::whereRaw("name LIKE ?", ['test%.com'])->get();
        $this->assertCount(1, $domains);
        $this->assertEquals($this->partner1->id, $domains->first()->partner_id);
    }

    /** @test */
    public function multiple_models_maintain_isolation_in_same_request()
    {
        // Create data for both partners
        Domain::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Domain::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);
        
        Invoice::factory()->create(['partner_id' => $this->partner1->id, 'client_id' => $this->client1->id]);
        Invoice::factory()->create(['partner_id' => $this->partner2->id, 'client_id' => $this->client2->id]);

        WalletTransaction::factory()->create(['wallet_id' => $this->wallet1->id, 'partner_id' => $this->partner1->id]);
        WalletTransaction::factory()->create(['wallet_id' => $this->wallet2->id, 'partner_id' => $this->partner2->id]);

        // Act as partner 1 user
        $this->actingAs($this->partner1User);

        // All models should be isolated
        $this->assertCount(1, Domain::all());
        $this->assertCount(1, Invoice::all());
        $this->assertCount(1, WalletTransaction::all());

        // All should belong to partner 1
        $this->assertEquals($this->partner1->id, Domain::first()->partner_id);
        $this->assertEquals($this->partner1->id, Invoice::first()->partner_id);
        $this->assertEquals($this->partner1->id, WalletTransaction::first()->partner_id);
    }
}
